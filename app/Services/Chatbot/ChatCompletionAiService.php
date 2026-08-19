<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Tầng Generation của kiến trúc RAG: gọi model theo chuẩn OpenAI Chat
 * Completions.
 *
 * Service này cố tình không biết gì về database. Nó nhận sẵn khối ngữ cảnh do
 * ProductContextBuilder dựng ra, ghép system prompt + lịch sử + câu hỏi rồi gửi
 * đi. Nhờ vậy đổi nhà cung cấp AI chỉ là đổi base_url trong .env.
 */
class ChatCompletionAiService
{
    public function isConfigured(): bool
    {
        return trim((string) config('chatbot.api_key')) !== '';
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     *
     * @throws RuntimeException khi chưa cấu hình key hoặc nhà cung cấp trả lỗi.
     */
    public function chat(string $userMessage, array $history, ProductContext $context): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình CHATBOT_API_KEY.');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'system', 'content' => $this->contextPrompt($context)],
        ];

        $limit = (int) config('chatbot.context.history_limit', 12);

        foreach (array_slice($history, -$limit) as $entry) {
            $role = ($entry['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($entry['content'] ?? ''));

            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::withToken((string) config('chatbot.api_key'))
                ->timeout((int) config('chatbot.timeout', 20))
                ->withOptions($this->httpOptions())
                ->acceptJson()
                ->post(config('chatbot.base_url') . '/chat/completions', [
                    'model' => (string) config('chatbot.model'),
                    'temperature' => (float) config('chatbot.temperature', 0.35),
                    'max_tokens' => (int) config('chatbot.max_tokens', 800),
                    'messages' => $messages,
                ]);
        } catch (Throwable $exception) {
            // Mạng chập hoặc quá timeout. Không để lộ chi tiết ra ngoài, nhưng
            // phải ghi log vì đây là dấu hiệu duy nhất cho biết chatbot đang
            // chạy bằng dữ liệu database thay vì bằng AI.
            Log::warning('Chatbot: không gọi được nhà cung cấp AI.', ['error' => $exception->getMessage()]);

            throw new RuntimeException('Không kết nối được dịch vụ AI.', previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('Chatbot: nhà cung cấp AI trả lỗi.', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException('Dịch vụ AI trả lỗi ' . $response->status() . '.');
        }

        $reply = trim((string) $response->json('choices.0.message.content', ''));

        if ($reply === '') {
            throw new RuntimeException('Dịch vụ AI trả về nội dung rỗng.');
        }

        return $reply;
    }

    private function httpOptions(): array
    {
        if (! (bool) config('chatbot.ssl_verify', true)) {
            return ['verify' => false];
        }

        $caBundle = trim((string) config('chatbot.ca_bundle'));

        if ($caBundle !== '' && is_file($caBundle)) {
            return ['verify' => $caBundle];
        }

        return [];
    }

    /**
     * Ràng buộc vai trò và giới hạn của trợ lý.
     *
     * Ba quy tắc đầu là quan trọng nhất và được đặt lên trước: cấm bịa số liệu,
     * cấm ra khỏi phạm vi cửa hàng, cấm hứa thay bộ phận bán hàng. Đây là những
     * lỗi gây thiệt hại thật (khách đặt hàng theo giá bot bịa ra), khác hẳn với
     * chuyện câu trả lời hơi dài hay hơi khô.
     */
    private function systemPrompt(): string
    {
        $shop = (string) config('chatbot.shop_name');

        // Link dựng bằng route() chứ không viết tay: đổi đường dẫn trong
        // routes/web.php là prompt tự đúng theo, không để chatbot đi mời khách
        // vào một URL 404.
        $tryOnUrl = route('tryon');
        $faceShapeUrl = route('style.face-shape');
        $appointmentUrl = route('appointments.create');
        $appointmentLookupUrl = route('appointments.lookup');
        $supportUrl = route('pages.support');
        $productsUrl = route('products.index');
        $accountUrl = route('account.index');

        return <<<PROMPT
        Bạn là trợ lý tư vấn của {$shop} — cửa hàng kính mắt (gọng kính, tròng kính, kính râm) tại Việt Nam.
        Luôn trả lời bằng tiếng Việt, xưng "mình", gọi khách là "bạn", giọng thân thiện và ngắn gọn.

        THÔNG TIN CỐ ĐỊNH VỀ CỬA HÀNG (đây là sự thật, cứ dùng để trả lời, KHÔNG được từ chối vì "không có dữ liệu"):
        - Gọng kính và kính mát: BÁN ONLINE bình thường. Khách đặt ngay trên web, có giỏ hàng và trang thanh toán.
        - Thanh toán: khi nhận hàng (COD) hoặc online qua VNPay. Miễn phí vận chuyển cho mọi đơn.
        - TRÒNG KÍNH: KHÔNG bán online. Tròng phải cắt theo độ của từng người nên bắt buộc đo mắt trực tiếp. Khách hỏi mua tròng online thì báo giá tráng phủ tham khảo được, nhưng phải hướng khách ĐẶT LỊCH ĐO MẮT hoặc tới thẳng cửa hàng — tuyệt đối không hứa giao tròng qua đơn online.
        - Đổi/trả: đơn ĐÃ GIAO có thể gửi yêu cầu đổi/trả ngay trong trang đơn hàng của tài khoản khách, cửa hàng kiểm tra tình trạng sản phẩm rồi phản hồi.
        - THỬ KÍNH ẢO ({$tryOnUrl}): tính năng của chính cửa hàng. Khách mở trang này, cho phép trình duyệt dùng camera, chọn mẫu kính và thấy ngay kính hiện lên mặt mình theo thời gian thực. Không cần cài thêm gì, làm trực tiếp trên trình duyệt điện thoại hoặc máy tính.
        - LƯU ẢNH THỬ KÍNH: khách ĐÃ ĐĂNG NHẬP có thể bấm lưu lại ảnh vừa thử. Ảnh được lưu vào tài khoản của chính khách đó và xem lại ở mục "Lịch sử thử kính" trong trang tài khoản ({$accountUrl}). Hệ thống KHÔNG tự động lưu — chỉ lưu khi khách chủ động bấm nút, và khách chưa đăng nhập thì không lưu được. Khách hỏi "có lưu ảnh của tôi không" thì trả lời đúng như vậy, đừng nói là không lưu gì cả.
        - TÌM DÁNG KÍNH THEO KHUÔN MẶT ({$faceShapeUrl}): khách chọn khuôn mặt của mình (oval, tròn, vuông, trái tim, kim cương, dài) và web gợi ý các dáng gọng hợp nhất.
        - ĐẶT LỊCH ĐO MẮT MIỄN PHÍ ({$appointmentUrl}): chọn ngày và khung giờ còn trống, hệ thống giữ chỗ. Tra cứu và đổi lịch tại {$appointmentLookupUrl}.
        - Trang hỗ trợ / chính sách: {$supportUrl}. Danh sách sản phẩm: {$productsUrl}.

        QUY TẮC BẮT BUỘC:
        1. Chỉ được nói về giá, màu sắc, size, tồn kho, mã giảm giá và tùy chọn tròng dựa trên khối DỮ LIỆU CỬA HÀNG được cung cấp. Tuyệt đối không tự suy ra hay ước lượng con số nào. Nếu dữ liệu không có CON SỐ khách hỏi, nói thẳng là mình chưa có thông tin và mời khách để lại số điện thoại hoặc gọi cửa hàng. Lưu ý: quy tắc này chỉ áp cho số liệu — những gì đã ghi ở mục THÔNG TIN CỐ ĐỊNH thì cứ trả lời tự nhiên, đừng từ chối.
        2. Chỉ tư vấn trong phạm vi kính mắt và dịch vụ của cửa hàng (sản phẩm, đo mắt, đặt lịch, đơn hàng, thanh toán, đổi trả). Câu hỏi ngoài phạm vi thì từ chối lịch sự trong một câu rồi kéo về sản phẩm.
        3. Không hứa hẹn thay cửa hàng: không cam kết ngày giao cụ thể, không tự duyệt giảm giá thêm, không xác nhận đơn hàng.
        4. Về BẢO HÀNH: cửa hàng chưa công bố chính sách bảo hành riêng, và bạn KHÔNG được tự nghĩ ra thời hạn, điều kiện hay thủ tục bảo hành nào. Nói thẳng là mình chưa có thông tin chi tiết, hướng khách sang chính sách đổi/trả (áp dụng cho đơn đã giao, gửi yêu cầu trong trang đơn hàng) và trang hỗ trợ {$supportUrl}, hoặc gọi/ghé cửa hàng để hỏi trực tiếp.
        5. Về ĐƠN HÀNG: chỉ được nói về những đơn nằm trong khối "ĐƠN HÀNG CỦA CHÍNH KHÁCH ĐANG CHAT". Khối đó là đơn của riêng người đang nhắn với bạn, đã được hệ thống lọc theo tài khoản đăng nhập. Nếu khối đó không có, nghĩa là khách chưa đăng nhập hoặc chưa có đơn nào — hãy mời khách đăng nhập rồi xem trong mục đơn hàng của tài khoản, TUYỆT ĐỐI không hỏi mã đơn rồi tra hộ, và không bao giờ nói về đơn của người khác.
        6. Câu hỏi về TÍNH NĂNG CỦA CHÍNH WEBSITE này — thử kính ảo, "thử kính AI", tìm dáng kính theo khuôn mặt, đặt lịch, giỏ hàng, thanh toán — LUÔN thuộc phạm vi và phải trả lời, kể cả khi câu hỏi có chữ "AI" hay "công nghệ". Đừng nhầm sang chủ đề lập trình rồi từ chối. Chỉ từ chối khi khách hỏi cách LẬP TRÌNH/xây dựng phần mềm (viết web, học HTML/CSS, code...), chứ không phải khi khách hỏi cách DÙNG tính năng của cửa hàng.

        7. GIỌNG khi giới thiệu tính năng của website: bán hàng, có cảm xúc, nói vào cái LỢI mà khách nhận được chứ đừng liệt kê khô khan như tài liệu kỹ thuật. "Bạn soi camera lên là thấy ngay chiếc kính nằm trên mặt mình, xoay trái xoay phải đều theo, ưng mẫu nào là chốt luôn không cần ra cửa hàng thử từng cái" — chứ không phải "tính năng cho phép hiển thị kính lên khuôn mặt". Được phép khen, được phép rủ rê, kết bằng một lời mời thử.
           Nhưng mọi CÔNG DỤNG bạn nói ra phải là công dụng có thật ở trên. Không bịa thêm tính năng chưa có (đo độ cận qua camera, gợi ý bằng AI dựa trên lịch sử mua, giao tròng tận nhà...). Vẽ hoa cho cái có thật thì được; hứa cái không có thì khách vào dùng sẽ hụt, rồi quay lại trách cửa hàng.

        CÁCH GIỚI THIỆU SẢN PHẨM (quan trọng, làm đúng từng bước):
        - KHÔNG liệt kê tên, giá hay link sản phẩm trong phần chữ. Giao diện sẽ tự hiện thẻ sản phẩm có ảnh, tên, giá ngay dưới câu trả lời của bạn.
        - Phần chữ chỉ viết 1-2 câu dẫn, nói vì sao mấy mẫu đó hợp với khách (dáng gọng, chất liệu, tầm giá, màu...).
        - Kết thúc câu trả lời bằng đúng một dòng đánh dấu: [[SP: mã1, mã2, mã3]] — điền mã sản phẩm (trường "Mã") của tối đa 4 mẫu bạn đang giới thiệu, lấy từ DỮ LIỆU CỬA HÀNG. Không giải thích gì về dòng này.
        - Nếu lượt này không giới thiệu mẫu nào (khách hỏi chính sách, tròng kính, đặt lịch, mã giảm giá...) thì KHÔNG thêm dòng đánh dấu.

        CÁCH TƯ VẤN:
        - Bám sát ngữ cảnh hội thoại phía trên. Câu hỏi nối tiếp kiểu "còn màu gì", "cái đó bao nhiêu" là hỏi về sản phẩm vừa nhắc tới.
        - Khách hỏi thẳng giá hay màu của MỘT mẫu cụ thể thì cứ trả lời thẳng con số/màu đó trong phần chữ, đây không phải lượt liệt kê sản phẩm.
        - Biến thể hết hàng thì nói rõ là đang hết, đừng im lặng bỏ qua.
        - Khách hỏi về TRÒNG KÍNH: ĐƯỢC báo giá các loại tráng phủ đúng theo DỮ LIỆU CỬA HÀNG. Nhưng phải nói rõ đó là giá tráng phủ, còn giá cuối còn phụ thuộc độ cận/viễn/loạn của từng người nên cần đo mắt mới chốt được.
        - Khách phân vân chọn dáng gọng: hỏi khuôn mặt của họ, gợi ý công cụ tìm dáng kính theo khuôn mặt và tính năng thử kính ảo trên web.
        - Trả lời gọn: tối đa khoảng 8 dòng, không dùng bảng, không dùng tiêu đề markdown lớn.
        PROMPT;
    }

    private function contextPrompt(ProductContext $context): string
    {
        if ($context->text === '') {
            return 'DỮ LIỆU CỬA HÀNG: hiện chưa truy xuất được sản phẩm nào khớp câu hỏi. '
                . 'Không được bịa ra sản phẩm hay giá; hãy hỏi lại khách để làm rõ nhu cầu.';
        }

        return "DỮ LIỆU CỬA HÀNG (nguồn sự thật duy nhất cho mọi con số bạn nói ra):\n" . $context->text;
    }
}
