<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\FrameMaterial;
use App\Models\FrameShape;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Từ vựng ngành kính lấy thẳng từ dữ liệu cửa hàng.
 *
 * Sinh ra để vá đúng một lỗ hổng: danh sách từ khóa viết tay trong
 * MessageClassifier không bao giờ liệt kê hết được tên thương hiệu, danh mục,
 * dáng gọng, chất liệu và màu mà cửa hàng đang bán. Thiếu nó thì câu hỏi thật
 * như "Ray-Ban RB4165 còn hàng không" bị coi là không thuộc phạm vi, còn admin
 * thêm thương hiệu mới thì phải sửa code mới nhận ra.
 */
class CatalogVocabulary
{
    private const CACHE_KEY = 'chatbot.catalog_vocabulary.v1';

    // Bỏ từ quá ngắn. Danh mục "Nam", "Nữ" sau khi bỏ dấu thành "nam", "nu" —
    // trùng với "năm", "nữa" trong câu nói thường và biến gần như mọi tin nhắn
    // thành "có tín hiệu ngành kính".
    private const MIN_TERM_LENGTH = 4;

    public function __construct(
        private readonly MessageClassifier $classifier,
    ) {}

    /**
     * Tin nhắn có nhắc tới thứ gì đó trong catalog không?
     *
     * @param  string  $normalizedMessage  chuỗi đã qua MessageClassifier::normalize()
     */
    public function mentions(string $normalizedMessage): bool
    {
        if ($normalizedMessage === '') {
            return false;
        }

        // So thêm một lượt trên bản đã bỏ hết khoảng trắng: khách gõ "ray ban"
        // còn trong database là "RayBan", hai chuỗi này không khớp nhau nếu chỉ
        // so nguyên văn.
        $collapsed = str_replace(' ', '', $normalizedMessage);

        foreach ($this->terms() as $term) {
            if ($this->classifier->matchesAny($normalizedMessage, [$term])) {
                return true;
            }

            if (str_contains($collapsed, str_replace(' ', '', $term))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function terms(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function (): array {
            try {
                $names = collect()
                    ->merge(Brand::query()->pluck('name'))
                    ->merge(Category::query()->pluck('name'))
                    ->merge(FrameShape::query()->pluck('name'))
                    ->merge(FrameMaterial::query()->pluck('name'))
                    ->merge(Color::query()->pluck('name'));
            } catch (Throwable) {
                // Database chưa sẵn sàng (lúc cài đặt, lúc chạy migrate) không
                // được phép làm chết endpoint chat; chỉ mất phần tín hiệu này.
                return [];
            }

            return $names
                ->map(fn ($name): string => $this->classifier->normalize((string) $name))
                ->filter(fn (string $term): bool => mb_strlen($term) >= self::MIN_TERM_LENGTH)
                ->unique()
                ->values()
                ->all();
        });
    }
}
