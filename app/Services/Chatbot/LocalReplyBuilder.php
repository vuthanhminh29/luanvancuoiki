<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

/**
 * Chế độ trả lời không cần AI.
 *
 * Được dùng trong hai tình huống, và cả hai đều là tình huống thật:
 *  - Chưa cấu hình CHATBOT_API_KEY (máy dev, môi trường demo, lúc hết hạn mức).
 *  - Đã cấu hình nhưng nhà cung cấp lỗi hoặc quá timeout.
 *
 * Câu trả lời ở đây ghép thẳng từ dữ liệu đã truy xuất nên kém tự nhiên hơn,
 * nhưng bù lại không bao giờ sai số liệu. Thà khô mà đúng còn hơn để khách nhìn
 * thấy "Xin lỗi, hệ thống đang bận".
 */
class LocalReplyBuilder
{
    // Không dùng từ khóa trần "trong" — trong tiếng Việt đó là giới từ ("trong
    // kho", "trong tuần") và sẽ nuốt nhầm hàng loạt câu hỏi khác.
    private const LENS_KEYWORDS = [
        'trong kinh', 'trong can', 'cat trong', 'lap trong', 'thay trong',
        'diop', 'do can', 'do loan', 'do vien', 'da trong', 'sieu mong',
        'chong choi', 'chong uv', 'chong xuoc', 'doi mau', 'trang phu',
        'anh sang xanh', 'chong xanh',
    ];

    public function __construct(
        private readonly MessageClassifier $classifier,
    ) {}

    /**
     * Câu hỏi này có phải đang hỏi về sản phẩm không?
     *
     * Controller dùng kết quả này để quyết định CÓ đính kèm thẻ sản phẩm hay
     * không. Trước đây thẻ luôn được gửi kèm, nên khách hỏi "tròng kính giá bao
     * nhiêu" nhận về lời khuyên đi đo mắt mà bên dưới lại hiện 4 thẻ kính râm
     * Ray-Ban — đúng dữ liệu nhưng lạc hoàn toàn với câu đang trả lời.
     *
     * Thứ tự điều kiện phải khớp đúng với fromContext() để hai bên không lệch.
     */
    public function isProductQuestion(string $message): bool
    {
        $normalized = $this->classifier->normalize($message);

        // Hỏi mẫu nào đang sale vẫn là câu hỏi về sản phẩm, có thẻ như thường.
        if ($this->classifier->asksForDiscountedProducts($normalized)) {
            return true;
        }

        if ($this->classifier->asksForPromoCode($normalized)) {
            return false;
        }

        // Câu hỏi về tròng không kèm thẻ gọng kính — đó chính là lỗi cũ: khách
        // hỏi tròng, nhận lời khuyên đi đo mắt, bên dưới lại hiện 4 thẻ kính râm.
        if ($this->contains($normalized, self::LENS_KEYWORDS)) {
            return false;
        }

        return $this->policyReply($normalized) === null;
    }

    public function farewell(): string
    {
        return 'Cảm ơn bạn đã nhắn cho ' . config('chatbot.shop_name') . '. '
            . 'Khi nào cần tư vấn thêm về gọng, tròng kính hay muốn đặt lịch đo mắt, bạn cứ mở khung chat này lên nhé. Chúc bạn một ngày tốt lành!';
    }

    public function offTopic(): string
    {
        return 'Xin lỗi bạn, mình là trợ lý của ' . config('chatbot.shop_name')
            . ' nên chỉ hỗ trợ được các nội dung về kính mắt: chọn gọng, chọn tròng, giá, tồn kho, đơn hàng và đặt lịch đo mắt. '
            . 'Bạn đang cần tìm mẫu kính như thế nào để mình gợi ý giúp?';
    }

    /**
     * Câu trả lời dựng từ ngữ cảnh RAG.
     */
    public function fromContext(string $message, ProductContext $context): string
    {
        $normalized = $this->classifier->normalize($message);

        // Thứ tự ba nhánh dưới đây là có chủ đích:
        //  1. Hỏi MẶT HÀNG nào đang giảm -> liệt kê hàng sale.
        //  2. Hỏi MÃ giảm giá -> liệt kê mã.
        //  3. Câu hỏi chính sách (tròng, ship, đổi trả, đặt lịch...).
        // Đảo 1 và 2 thì "sản phẩm nào đang giảm giá" bị nhánh mã nuốt mất; đưa
        // 3 lên trước thì "có mã giảm giá cho tròng kính không" lại rơi vào
        // nhánh tư vấn tròng thay vì trả lời cái mã khách hỏi.
        if ($this->classifier->asksForDiscountedProducts($normalized)) {
            return $this->discountedProductsReply($context);
        }

        if ($this->classifier->asksForPromoCode($normalized)) {
            return $this->promotionReply($context);
        }

        // Nhánh tròng cần dữ liệu tráng phủ nên phải nằm ở đây (có $context),
        // không nằm trong policyReply.
        if ($this->contains($normalized, self::LENS_KEYWORDS)) {
            return $this->lensReply($context);
        }

        if ($policyReply = $this->policyReply($normalized)) {
            return $policyReply;
        }

        if ($context->isEmpty()) {
            return 'Mình chưa tìm thấy mẫu nào khớp với mô tả của bạn. '
                . 'Bạn cho mình biết rõ hơn về thương hiệu, dáng gọng (vuông, tròn, mắt mèo...), chất liệu hoặc tầm giá mong muốn nhé — '
                . 'hoặc xem toàn bộ sản phẩm tại ' . route('products.index') . '.';
        }

        $lines = ['Dựa trên dữ liệu cửa hàng, mình gợi ý bạn mấy mẫu này:'];

        foreach ($context->products->take(4) as $product) {
            $line = '• ' . $product['name'] . ' — ' . $this->money($product['display_price']);

            if ($product['brand']) {
                $line .= ' (' . $product['brand'] . ')';
            }

            $line .= $product['total_stock'] > 0
                ? ' — còn ' . $product['total_stock'] . ' sản phẩm'
                : ' — đang hết hàng';

            $lines[] = $line;
            $lines[] = '  ' . $product['url'];

            $colors = collect($product['variants'])
                ->filter(fn (array $variant): bool => $variant['stock'] > 0 && $variant['color'])
                ->pluck('color')
                ->unique()
                ->take(5);

            if ($colors->isNotEmpty()) {
                $lines[] = '  Màu còn hàng: ' . $colors->implode(', ');
            }
        }

        if ($context->promotions->isNotEmpty()) {
            $promotion = $context->promotions->first();

            $lines[] = '';
            $lines[] = 'Đang có mã ' . $promotion['code'] . ' giảm ' . $this->discountLabel($promotion)
                . ($promotion['min_order_amount'] > 0 ? ' cho đơn từ ' . $this->money($promotion['min_order_amount']) : '') . '.';
        }

        $lines[] = '';
        $lines[] = 'Bạn muốn mình lọc theo tầm giá, dáng gọng hay thương hiệu cụ thể nào không?';

        // Cố định "\n" thay vì PHP_EOL: máy dev chạy Windows nên PHP_EOL là
        // "\r\n", server chạy Linux thì là "\n" — cùng một câu trả lời mà ra hai
        // định dạng khác nhau tùy nơi chạy.
        return implode("\n", $lines);
    }

    /**
     * Tròng kính: báo giá được, nhưng KHÔNG bán online.
     *
     * Hai nửa của câu trả lời này đều bắt buộc. Bỏ nửa giá thì khách hỏi giá mà
     * không nhận được giá; bỏ nửa "không bán online" thì khách tưởng đặt tròng
     * trên web được, trong khi tròng phải cắt theo độ nên bắt buộc đo mắt trực
     * tiếp — hứa sai ở đây là hứa thay cửa hàng.
     */
    private function lensReply(ProductContext $context): string
    {
        $lines = [];

        if ($context->lensOptions->isNotEmpty()) {
            $lines[] = 'Giá các loại tráng phủ tròng bên mình:';

            foreach ($context->lensOptions as $option) {
                $lines[] = '• ' . $option['name'] . ' — ' . $this->money($option['price']);
            }

            $lines[] = '';
            $lines[] = 'Đây là giá tráng phủ tham khảo; giá cuối còn tuỳ độ cận/loạn/viễn của bạn nên phải đo mắt mới chốt được.';
        } else {
            $lines[] = 'Giá tròng tuỳ độ cận/loạn/viễn và loại tráng phủ bạn chọn, nên phải đo mắt mới báo chính xác được.';
        }

        $lines[] = '';
        $lines[] = 'Tròng kính bên mình không bán online được bạn nhé — tròng phải cắt riêng theo độ của từng người. '
            . 'Bạn đặt lịch đo mắt miễn phí tại ' . route('appointments.create')
            . ' hoặc ghé thẳng cửa hàng để kỹ thuật viên đo và tư vấn trực tiếp, cũng là để chọn tròng hợp với chính mẫu gọng bạn thích.';

        return implode("\n", $lines);
    }

    private function discountedProductsReply(ProductContext $context): string
    {
        // Lọc lại một lần nữa ở đây dù ProductContextBuilder đã lọc theo
        // sale_price: nếu sau này có ai đổi cách truy xuất, chỗ này vẫn không
        // gắn nhãn "đang giảm giá" lên một mẫu bán đúng giá gốc.
        $discounted = $context->products
            ->filter(fn (array $product): bool => $product['sale_price'] !== null
                && $product['base_price'] > 0
                && $product['sale_price'] < $product['base_price'])
            ->values();

        if ($discounted->isEmpty()) {
            $reply = 'Hiện chưa có mẫu nào được giảm giá trực tiếp bạn nhé.';

            if ($context->promotions->isNotEmpty()) {
                $promotion = $context->promotions->first();

                return $reply . ' Nhưng bạn vẫn dùng được mã ' . $promotion['code']
                    . ' giảm ' . $this->discountLabel($promotion) . ' ở bước thanh toán.';
            }

            return $reply . ' Bạn xem toàn bộ mẫu đang bán tại ' . route('products.index') . '.';
        }

        $lines = ['Các mẫu đang giảm giá trực tiếp:'];

        foreach ($discounted->take(5) as $product) {
            $percent = (int) round((1 - $product['sale_price'] / $product['base_price']) * 100);

            $lines[] = '• ' . $product['name'] . ' — ' . $this->money($product['sale_price'])
                . ' (giá gốc ' . $this->money($product['base_price']) . ', giảm ' . $percent . '%)'
                . ($product['total_stock'] > 0 ? ' — còn ' . $product['total_stock'] . ' sản phẩm' : ' — đang hết hàng');
            $lines[] = '  ' . $product['url'];
        }

        if ($context->promotions->isNotEmpty()) {
            $promotion = $context->promotions->first();

            $lines[] = '';
            $lines[] = 'Ngoài ra bạn còn dùng được mã ' . $promotion['code']
                . ' giảm ' . $this->discountLabel($promotion) . ' ở bước thanh toán.';
        }

        return implode("\n", $lines);
    }

    private function promotionReply(ProductContext $context): string
    {
        if ($context->promotions->isEmpty()) {
            return 'Hiện chưa có mã giảm giá nào đang chạy bạn nhé. '
                . 'Bạn xem trước các mẫu đang bán tại ' . route('products.index') . ', có chương trình mới mình sẽ báo ngay.';
        }

        $lines = ['Các mã giảm giá đang chạy:'];

        foreach ($context->promotions as $promotion) {
            $line = '• ' . $promotion['code'] . ' — giảm ' . $this->discountLabel($promotion);

            if ($promotion['max_discount_amount'] !== null) {
                $line .= ', tối đa ' . $this->money($promotion['max_discount_amount']);
            }

            if ($promotion['min_order_amount'] > 0) {
                $line .= ', đơn từ ' . $this->money($promotion['min_order_amount']);
            }

            if ($promotion['end_at'] !== null) {
                $line .= ', dùng đến ' . $promotion['end_at'];
            }

            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Bạn nhập mã ở bước thanh toán là được. Cần mình gợi ý mẫu kính nào để dùng mã không?';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $promotion
     */
    private function discountLabel(array $promotion): string
    {
        if ($promotion['discount_type'] !== 'PERCENT') {
            return $this->money($promotion['discount_value']);
        }

        // Giá trị lưu dạng decimal nên 30 ra "30,00". Cắt phần thập phân thừa để
        // câu trả lời đọc như người viết, không như dump từ database.
        return rtrim(rtrim(number_format($promotion['discount_value'], 2, ',', '.'), '0'), ',') . '%';
    }

    /**
     * Trả lời các câu hỏi chính sách bằng thông tin cố định của hệ thống.
     *
     * Chỉ khẳng định những gì code thật sự đang làm (phí ship bằng 0, hai hình
     * thức thanh toán COD và VNPay). Phần còn lại dẫn khách sang trang hỗ trợ
     * thay vì đoán số ngày đổi trả.
     */
    private function policyReply(string $normalized): ?string
    {
        if ($this->contains($normalized, ['phi ship', 'phi van chuyen', 'phi giao hang', 'tien ship', 'ship bao nhieu', 'mien phi ship'])) {
            return 'Hiện ' . config('chatbot.shop_name') . ' miễn phí vận chuyển cho mọi đơn hàng, bạn không phải trả thêm phí giao hàng nào. '
                . 'Chi tiết về thời gian giao bạn xem thêm tại ' . route('pages.support') . '.';
        }

        if ($this->contains($normalized, ['thanh toan', 'tra tien', 'chuyen khoan', 'cod', 'vnpay', 'quet the'])) {
            return 'Cửa hàng nhận 2 hình thức: thanh toán khi nhận hàng (COD) và thanh toán online qua VNPay (thẻ nội địa, thẻ quốc tế, ví). '
                . 'Bạn chọn hình thức ngay ở bước thanh toán nhé.';
        }

        if ($this->contains($normalized, ['doi tra', 'tra hang', 'hoan tien', 'bao hanh', 'loi san pham'])) {
            return 'Đơn đã giao thành công có thể gửi yêu cầu đổi/trả ngay trong trang đơn hàng của tài khoản bạn, '
                . 'cửa hàng sẽ kiểm tra tình trạng sản phẩm rồi phản hồi. Điều kiện chi tiết bạn xem tại ' . route('pages.support') . '.';
        }

        if ($this->contains($normalized, ['dat lich', 'do mat', 'do thi luc', 'kham mat', 'lich hen'])) {
            return 'Bạn đặt lịch đo mắt miễn phí tại cửa hàng ở đây: ' . route('appointments.create') . '. '
                . 'Chọn ngày và khung giờ còn trống, hệ thống sẽ giữ chỗ cho bạn. Muốn đổi lịch thì tra cứu tại ' . route('appointments.lookup') . '.';
        }

        if ($this->contains($normalized, ['khuon mat', 'dang kinh', 'form kinh', 'hop mat', 'chon gong'])) {
            return 'Bạn thử công cụ tìm dáng kính theo khuôn mặt của cửa hàng nhé: ' . route('style.face-shape') . '. '
                . 'Chọn đúng khuôn mặt là ra ngay các dáng gọng hợp nhất. Ngoài ra bạn có thể thử kính ảo trực tiếp tại ' . route('tryon') . '.';
        }

        if ($this->contains($normalized, ['thu kinh', 'try on', 'thu ao', 'xem thu'])) {
            return 'Cửa hàng có tính năng thử kính ảo bằng camera tại ' . route('tryon') . ', bạn thử trực tiếp trên trình duyệt không cần cài gì thêm.';
        }

        return null;
    }

    /**
     * @param  list<string>  $needles
     */
    private function contains(string $haystack, array $needles): bool
    {
        // Dùng chung bộ so khớp theo ranh giới từ của MessageClassifier: "code"
        // không được kích hoạt nhánh trả lời về thanh toán COD.
        return $this->classifier->matchesAny($haystack, $needles);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
}
