<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

/**
 * Câu trả lời của model, đã tách phần dành cho người đọc và phần dành cho máy.
 *
 * Model được yêu cầu kết thúc bằng một dòng đánh dấu dạng `[[SP: ma1, ma2]]`
 * liệt kê mã sản phẩm nó đang giới thiệu. Có dòng này thì:
 *
 *  - Phần chữ chỉ cần một hai câu dẫn ("mấy mẫu này hợp với bạn"), không phải
 *    kể lại tên + giá + link của từng mẫu nữa. Trước đó model liệt kê đầy đủ
 *    trong text rồi bên dưới lại hiện y hệt bằng thẻ — cùng một thông tin đọc
 *    hai lần, mà bản text thì không có ảnh.
 *  - Việc chọn thẻ nào không còn phải đoán bằng cách dò tên sản phẩm trong câu
 *    trả lời. Model nói thẳng nó đang giới thiệu mẫu nào.
 *
 * Dòng đánh dấu luôn bị cắt khỏi text trước khi trả về cho khách.
 */
class AiReply
{
    private const MARKER_PATTERN = '/\[\[\s*SP\s*:\s*([^\]]*)\]\]/iu';

    /**
     * @param  list<string>  $productCodes
     */
    private function __construct(
        public readonly string $text,
        public readonly array $productCodes,
    ) {}

    public static function parse(string $raw): self
    {
        $codes = [];

        if (preg_match(self::MARKER_PATTERN, $raw, $matches) === 1) {
            $codes = collect(explode(',', $matches[1]))
                ->map(fn (string $code): string => trim($code))
                ->filter(fn (string $code): bool => $code !== '')
                ->unique()
                ->values()
                ->all();
        }

        // Cắt dòng đánh dấu rồi dọn khoảng trắng thừa nó để lại ở cuối.
        $text = trim((string) preg_replace(self::MARKER_PATTERN, '', $raw));

        return new self($text, $codes);
    }
}
