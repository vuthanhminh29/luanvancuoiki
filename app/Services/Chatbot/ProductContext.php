<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use Illuminate\Support\Collection;

/**
 * Kết quả của một lượt truy xuất RAG.
 *
 * Tách riêng khỏi ProductContextBuilder vì hai bên tiêu thụ nó rất khác nhau:
 * ChatCompletionAiService cần bản văn bản để nhét vào prompt, còn
 * LocalReplyBuilder (chế độ không có API key) và widget lại cần dữ liệu có cấu
 * trúc để tự dựng câu trả lời và link sản phẩm.
 */
class ProductContext
{
    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @param  Collection<int, array<string, mixed>>  $promotions
     * @param  Collection<int, array<string, mixed>>  $lensOptions
     * @param  Collection<int, array<string, mixed>>  $orders  đơn của chính khách đang đăng nhập
     */
    public function __construct(
        public readonly Collection $products,
        public readonly Collection $promotions,
        public readonly Collection $lensOptions,
        public readonly Collection $orders,
        public readonly string $text,
    ) {}

    public function isEmpty(): bool
    {
        return $this->products->isEmpty();
    }
}
