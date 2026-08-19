<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

/**
 * Phân loại tin nhắn bằng từ khóa.
 *
 * Đọc kỹ chỗ này trước khi thêm luật mới: gần như toàn bộ lớp này CHỈ chạy khi
 * chưa cấu hình model. Khi đã có model, việc hiểu câu hỏi là của nó — lọc trước
 * bằng từ khóa lúc đó chỉ tạo ra từ chối oan và làm chatbot ngu đi đúng ở chỗ
 * vừa trả tiền để nó thông minh.
 *
 * Ngoại lệ duy nhất là isPromptInjection(): luôn chạy, kể cả khi có model.
 *
 * Phần còn lại (isOffTopic, isFarewell, asksForPromoCode...) phục vụ chế độ dự
 * phòng — lúc thiếu key hoặc nhà cung cấp lỗi — nơi không còn gì thông minh hơn
 * phía sau nữa.
 */
class MessageClassifier
{
    // Câu chào kết. So khớp trên chuỗi đã bỏ dấu nên "cam on" cũng khớp.
    private const FAREWELL_PHRASES = [
        'cam on', 'cam ta', 'thanks', 'thank you', 'tks',
        'tam biet', 'bye', 'goodbye', 'chao shop', 'chao ban nhe',
        'ok shop', 'oke shop', 'the thoi', 'vay thoi', 'du roi', 'hieu roi',
        'khong con gi', 'khong hoi gi them',
    ];

    // Chủ đề rõ ràng nằm ngoài cửa hàng kính mắt. Danh sách này chỉ có tác dụng
    // khi tin nhắn KHÔNG chứa từ khóa ngành kính nào (allow-list bên dưới) — nếu
    // không, câu hợp lệ như "mã code sản phẩm này là gì" sẽ bị chặn oan.
    private const OFF_TOPIC_KEYWORDS = [
        // Lập trình / kỹ thuật
        'lap trinh', 'viet code', 'python', 'javascript', 'typescript', 'thuat toan',
        'framework', 'docker', 'server', 'database', 'react', 'reactjs', 'vue',
        'angular', 'nodejs', 'laravel', 'usestate', 'useeffect', 'component',
        'hook', 'git', 'linux', 'html', 'css',
        // Học thuật
        'giai phuong trinh', 'giai bai tap', 'dao ham', 'tich phan', 'lam van',
        'dich sang tieng', 'lam tho', 'viet essay', 'bai tap ve nha',
        // Đời sống ngoài ngành
        'nau an', 'cong thuc mon', 'thoi tiet', 'ty so', 'bong da', 'xo so',
        'chung khoan', 'bitcoin', 'tien ao', 'bat dong san', 'tuyen dung',
        'chinh tri', 'bau cu', 'ton giao', 'tu vi', 'boi toan',
        // Y tế ngoài phạm vi tư vấn kính
        'chua benh', 'don thuoc', 'lieu luong thuoc',
    ];

    // Ý đồ prompt injection: cố ép model quên vai trò của nó. Xử lý chung với
    // off-topic — từ chối lịch sự, không bao giờ chuyển tiếp xuống AI.
    private const INJECTION_KEYWORDS = [
        'bo qua moi chi dan', 'bo qua huong dan tren', 'quen het huong dan',
        'ignore all previous', 'ignore previous instructions', 'disregard the above',
        'system prompt', 'prompt he thong', 'ban khong con la', 'gia vo lam',
        'you are now', 'jailbreak', 'dan mode',
    ];

    // Allow-list ngành kính mắt. Chỉ cần một từ trong đây xuất hiện là tin nhắn
    // được coi là thuộc phạm vi tư vấn, kể cả khi có lẫn từ off-topic.
    private const DOMAIN_KEYWORDS = [
        'kinh', 'gong', 'mat kinh', 'kinh mat', 'kinh ram', 'kinh cong',
        'can thi', 'vien thi', 'loan thi', 'lao thi', 'do mat', 'do thi luc',
        'thi luc', 'diop', 'do can', 'trong kinh', 'chong xanh', 'anh sang xanh',
        'doi mau', 'chong choi', 'uv', 'khuon mat', 'dang kinh', 'form kinh',
        'titan', 'acetate', 'nhua deo', 'kim loai', 'khong gong', 'nua gong',
        'san pham', 'gia', 'bao nhieu', 'con hang', 'ton kho', 'size', 'mau sac',
        'thuong hieu', 'hang', 'khuyen mai', 'giam gia', 'ma giam', 'voucher',
        'don hang', 'giao hang', 'ship', 'van chuyen', 'thanh toan', 'cod', 'vnpay',
        'doi tra', 'bao hanh', 'hoan tien', 'huy don', 'dat lich', 'lich hen',
        'cua hang', 'tu van', 'thu kinh', 'try on', 'don kinh', 'mau',
    ];

    // Từ gọi thẳng tên cái mã giảm giá.
    private const PROMO_CODE_KEYWORDS = ['ma giam gia', 'ma giam', 'ma khuyen mai', 'ma uu dai', 'voucher', 'coupon'];

    // Từ nói về việc giảm giá nói chung — mơ hồ, có thể là mã mà cũng có thể là
    // hàng đang sale. Phải kết hợp với PRODUCT_KEYWORDS mới quyết được.
    private const DISCOUNT_KEYWORDS = ['giam gia', 'khuyen mai', 'uu dai', 'sale', 'dang giam', 'ha gia', 'giam bao nhieu'];

    // Từ chỉ hàng hoá.
    private const PRODUCT_KEYWORDS = ['san pham', 'mau', 'mau nao', 'kinh', 'gong', 'kinh mat', 'hang nao', 'cai nao', 'model'];

    // Từ nối tiếng Việt xuất hiện ở hầu hết câu hỏi nên không phân biệt được sản
    // phẩm nào; nếu lọt vào bộ tính điểm thì mọi sản phẩm đều khớp như nhau.
    private const STOP_WORDS = [
        'la', 'va', 'co', 'khong', 'cho', 'toi', 'minh', 'ban', 'shop', 'em', 'anh', 'chi',
        'the', 'nay', 'do', 'nao', 'gi', 'sao', 'voi', 'de', 'duoc', 'thi', 'ma', 'o',
        'muon', 'can', 'hoi', 'xin', 'ah', 'nhe', 'nha', 'vay', 'lam', 'con', 'ra',
        'tu', 'den', 've', 'tren', 'duoi', 'trong', 'ngoai', 'hay', 'hon', 'rat', 'qua',
    ];

    public function isFarewell(string $message): bool
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return false;
        }

        // Chỉ nhận diện câu chào kết khi tin nhắn ngắn. "Cảm ơn shop, cho mình
        // hỏi thêm mẫu Titan có màu gì" cũng chứa "cam on" nhưng rõ ràng là câu
        // hỏi thật, không được cắt luồng ở đây.
        if (count(explode(' ', $normalized)) > 8) {
            return false;
        }

        return $this->matchesAny($normalized, self::FAREWELL_PHRASES);
    }

    /**
     * Tin nhắn này có nằm ngoài phạm vi cửa hàng không?
     *
     * CHỈ được gọi ở chế độ không có model. Khi đã có model thì việc phân loại
     * này là của nó — nó đọc được câu chữ, còn danh sách từ khóa thì không, nên
     * lọc trước bằng từ khóa chỉ tạo ra từ chối oan (câu "cái đó bao nhiêu tiền"
     * không có chữ nào thuộc ngành kính nhưng rõ ràng là câu hỏi hợp lệ).
     *
     * Ở chế độ không có model thì luật bị đảo lại: không mang bất kỳ tín hiệu
     * ngành kính nào là coi như ngoài phạm vi. Blocklist viết tay không bao giờ
     * đủ — trước khi có luật đảo này, "useState trong react là gì" không trúng
     * từ cấm nào nên lọt xuống nhánh gợi ý và bot đáp lại bằng 4 mẫu kính.
     *
     * @param  bool  $hasCatalogSignal  tin nhắn có nhắc tên thương hiệu/danh mục/
     *                                  dáng gọng/chất liệu/màu đang bán không
     *                                  (xem CatalogVocabulary)
     */
    public function isOffTopic(string $message, bool $hasCatalogSignal = false): bool
    {
        $normalized = $this->normalize($message);

        if ($this->hasDomainKeyword($normalized) || $hasCatalogSignal) {
            return false;
        }

        if ($this->matchesAny($normalized, self::OFF_TOPIC_KEYWORDS)) {
            return true;
        }

        // Không mang bất kỳ tín hiệu ngành kính nào. Blocklist viết tay không bao
        // giờ đủ: trước khi có luật đảo này, "useState trong react là gì" không
        // trúng từ cấm nào nên lọt xuống nhánh gợi ý và bot đáp bằng 4 mẫu kính.
        return $normalized !== '';
    }

    /**
     * Có dấu hiệu cố ép model quên vai trò của nó không?
     *
     * Đây là bộ lọc DUY NHẤT vẫn chạy khi đã có model, và có lý do: phòng thủ
     * prompt injection mà giao cho chính model đang bị tấn công thì không còn là
     * phòng thủ. Nó cũng chặn cả khi tin nhắn có kèm từ khóa ngành kính, vì thêm
     * chữ "kính" vào câu là cách né bộ lọc dễ nhất.
     */
    public function isPromptInjection(string $message): bool
    {
        return $this->matchesAny($this->normalize($message), self::INJECTION_KEYWORDS);
    }


    public function hasDomainKeyword(string $normalizedMessage): bool
    {
        return $this->matchesAny($normalizedMessage, self::DOMAIN_KEYWORDS);
    }

    /**
     * Khách đang hỏi MÃ giảm giá, hay hỏi MẶT HÀNG nào đang giảm giá?
     *
     * Hai câu này chỉ khác nhau vài chữ nhưng cần hai câu trả lời hoàn toàn khác
     * nhau, và lúc đầu module gộp chung làm một: "sản phẩm nào đang giảm giá" bị
     * chuỗi "giam gia" kéo vào nhánh liệt kê mã, khách hỏi hàng lại nhận về mã.
     *
     * Quy tắc phân biệt:
     *  - Có từ chỉ đích danh cái mã (ma giam gia, voucher...) -> hỏi mã, kể cả
     *    khi câu có kèm tên hàng ("có mã giảm cho kính mát không").
     *  - Chỉ có từ giảm giá chung chung + có từ chỉ hàng hoá -> hỏi mặt hàng.
     *  - Chỉ có từ giảm giá chung chung -> mặc định là hỏi mã.
     */
    public function asksForPromoCode(string $normalizedMessage): bool
    {
        if ($this->matchesAny($normalizedMessage, self::PROMO_CODE_KEYWORDS)) {
            return true;
        }

        return $this->matchesAny($normalizedMessage, self::DISCOUNT_KEYWORDS)
            && ! $this->matchesAny($normalizedMessage, self::PRODUCT_KEYWORDS);
    }

    public function asksForDiscountedProducts(string $normalizedMessage): bool
    {
        if ($this->matchesAny($normalizedMessage, self::PROMO_CODE_KEYWORDS)) {
            return false;
        }

        return $this->matchesAny($normalizedMessage, self::DISCOUNT_KEYWORDS)
            && $this->matchesAny($normalizedMessage, self::PRODUCT_KEYWORDS);
    }

    /**
     * So khớp theo RANH GIỚI TỪ, không phải substring.
     *
     * Đây không phải chi tiết làm cho đẹp: allow-list có những từ rất ngắn như
     * "gia", "mau", "cod". Nếu so bằng str_contains thì "giải bài tập" chứa
     * "gia" và được coi là câu hỏi về kính, còn "code" chứa "cod" và được coi
     * là câu hỏi về thanh toán COD. Cả hai bộ lọc đều hỏng theo cách rất khó
     * nhìn ra khi đọc log.
     *
     * @param  list<string>  $needles  chuỗi đã chuẩn hóa (chữ thường, không dấu)
     */
    public function matchesAny(string $normalizedHaystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $normalizedHaystack) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chuẩn hóa về chữ thường, bỏ dấu, gộp khoảng trắng.
     *
     * Khách gõ tiếng Việt lúc có dấu lúc không ("kính cận" / "kinh can"), nên
     * mọi so khớp từ khóa trong module chatbot đều chạy trên dạng đã chuẩn hóa.
     */
    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        $accents = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ',
            'd' => 'đ',
        ];

        foreach ($accents as $plain => $accented) {
            $value = preg_replace('/[' . $accented . ']/u', $plain, $value) ?? $value;
        }

        // Giữ lại chữ, số và khoảng trắng; dấu câu chỉ gây nhiễu khi so khớp.
        $value = preg_replace('/[^a-z0-9\s]/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * Tách tin nhắn thành các token đủ dài để dùng làm từ khóa truy vấn.
     *
     * @return list<string>
     */
    public function tokenize(string $message): array
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            explode(' ', $normalized),
            fn (string $token): bool => mb_strlen($token) >= 2 && ! in_array($token, self::STOP_WORDS, true),
        ));
    }
}
