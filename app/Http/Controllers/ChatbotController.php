<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Chatbot\AiReply;
use App\Services\Chatbot\CatalogVocabulary;
use App\Services\Chatbot\ChatCompletionAiService;
use App\Services\Chatbot\LocalReplyBuilder;
use App\Services\Chatbot\MessageClassifier;
use App\Services\Chatbot\ProductContext;
use App\Services\Chatbot\ProductContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Endpoint chat của trợ lý tư vấn.
 *
 * Khi ĐÃ cấu hình model (đường chính):
 *  1. Chặn prompt injection — việc duy nhất không giao cho model.
 *  2. Dựng ngữ cảnh RAG từ database (sản phẩm, tồn kho, khuyến mãi, tròng kính).
 *  3. Đưa ngữ cảnh + lịch sử hội thoại cho model, để nó hiểu câu chữ và tự
 *     quyết định trả lời hay từ chối.
 *
 * Khi CHƯA cấu hình model (đường dự phòng, hoặc model lỗi):
 *  4. Lọc bằng từ khóa và ghép câu trả lời thẳng từ ngữ cảnh vừa dựng.
 *
 * Bước 4 kém thông minh hơn hẳn nhưng không bao giờ sai số liệu, và là lý do
 * endpoint này gần như không trả lỗi cho khách.
 */
class ChatbotController extends Controller
{
    // Trần độ dài một tin nhắn. Câu hỏi tư vấn kính thật hiếm khi vượt quá vài
    // trăm ký tự; để rộng hơn chỉ mở đường cho việc nhồi prompt và đốt token.
    private const MAX_MESSAGE_LENGTH = 500;

    public function __construct(
        private readonly MessageClassifier $classifier,
        private readonly CatalogVocabulary $vocabulary,
        private readonly ProductContextBuilder $contextBuilder,
        private readonly ChatCompletionAiService $ai,
        private readonly LocalReplyBuilder $localReply,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        // Lịch sử hội thoại do client gửi lên nên hoàn toàn không đáng tin: phải
        // chặn cả số lượng tin lẫn độ dài từng tin, nếu không người dùng có thể
        // nhét vài trăm KB văn bản vào một request và biến nó thành hóa đơn AI.
        $data = $request->validate([
            'message' => ['required', 'string', 'max:' . self::MAX_MESSAGE_LENGTH],
            'history' => ['nullable', 'array', 'max:' . (int) config('chatbot.context.history_limit', 12)],
            'history.*.role' => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $message = trim($data['message']);
        $history = array_values($data['history'] ?? []);

        if ($message === '') {
            return response()->json([
                'reply' => 'Bạn nhắn giúp mình nội dung cần tư vấn nhé.',
                'source' => 'validation',
                'products' => [],
            ]);
        }

        // Chốt chặn duy nhất chạy ở MỌI chế độ. Phòng thủ prompt injection không
        // được phép giao cho chính model đang bị tấn công.
        if ($this->classifier->isPromptInjection($message)) {
            return response()->json([
                'reply' => $this->localReply->offTopic(),
                'source' => 'off_topic',
                'products' => [],
            ]);
        }

        $aiAvailable = $this->ai->isConfigured();

        // Khi CÓ model: không lọc gì thêm bằng từ khóa. Model đọc được câu chữ,
        // hiểu được câu nối tiếp và tự từ chối câu ngoài phạm vi theo system
        // prompt — chặn trước bằng danh sách từ khóa chỉ tạo ra từ chối oan
        // ("cái đó bao nhiêu" đâu có chữ nào thuộc ngành kính).
        //
        // Khi KHÔNG có model: từ khóa là thứ duy nhất còn lại, nên mới phải siết.
        if (! $aiAvailable) {
            if ($this->classifier->isFarewell($message)) {
                return response()->json([
                    'reply' => $this->localReply->farewell(),
                    'source' => 'farewell',
                    'products' => [],
                ]);
            }

            $hasCatalogSignal = $this->vocabulary->mentions($this->classifier->normalize($message));

            if ($this->classifier->isOffTopic($message, $hasCatalogSignal)) {
                return response()->json([
                    'reply' => $this->localReply->offTopic(),
                    'source' => 'off_topic',
                    'products' => [],
                ]);
            }
        }

        // ID khách lấy từ PHIÊN ĐĂNG NHẬP, không bao giờ từ dữ liệu client gửi
        // lên. Nhận id qua payload nghĩa là ai cũng đọc được đơn của người khác
        // chỉ bằng cách sửa một con số trong request.
        $context = $this->contextBuilder->build($message, $history, $request->user()?->getAuthIdentifier());

        try {
            $parsed = AiReply::parse($this->ai->chat($message, $history, $context));
            $reply = $parsed->text;
            $products = $this->cardsFromCodes($parsed->productCodes, $context);
            $source = 'ai';
        } catch (Throwable) {
            // Lỗi đã được ChatCompletionAiService ghi log kèm nguyên nhân. Ở đây
            // chỉ cần chuyển sang chế độ dữ liệu, khách không cần biết vì sao.
            $reply = $this->localReply->fromContext($message, $context);
            $products = $this->localReply->isProductQuestion($message)
                ? $this->toCards($context->products)
                : collect();
            $source = 'database';
        }

        return response()->json([
            'reply' => $reply,
            'source' => $source,
            'products' => $products,
        ]);
    }

    /**
     * Thẻ sản phẩm theo đúng danh sách mã mà model tự khai.
     *
     * Không cần đoán ý định bằng từ khóa nữa: model đã nói thẳng nó đang giới
     * thiệu mẫu nào. Lượt nào không phải giới thiệu sản phẩm (hỏi chính sách,
     * tròng kính, mã giảm giá) thì nó không khai mã nào và ở đây không có thẻ.
     *
     * Mã vẫn phải đối chiếu ngược lại ngữ cảnh vừa truy xuất — model có thể gõ
     * nhầm hoặc bịa ra một mã không tồn tại, và thẻ thì dẫn thẳng tới trang sản
     * phẩm nên không được phép sai.
     *
     * @param  list<string>  $codes
     * @return Collection<int, array<string, mixed>>
     */
    private function cardsFromCodes(array $codes, ProductContext $context): Collection
    {
        if ($codes === []) {
            return collect();
        }

        $wanted = collect($codes)->map(fn (string $code): string => mb_strtolower($code));

        $products = $context->products
            ->filter(fn (array $product): bool => $wanted->contains(mb_strtolower((string) $product['code'])))
            // Giữ đúng thứ tự model đưa ra: nó xếp mẫu hợp nhất lên đầu.
            ->sortBy(fn (array $product): int => $wanted->search(mb_strtolower((string) $product['code'])))
            ->values();

        return $this->toCards($products);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return Collection<int, array<string, mixed>>
     */
    private function toCards(Collection $products): Collection
    {
        return $products->take(4)->map(fn (array $product): array => [
            'name' => $product['name'],
            'price' => $product['display_price'],
            'image' => $product['image'],
            'url' => $product['url'],
            'in_stock' => $product['total_stock'] > 0,
        ])->values();
    }
}
