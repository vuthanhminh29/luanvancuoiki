<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TryOnSnapshot extends Model
{
    // Các cột được phép lưu khi controller tạo kết quả thử kính mới.
    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
        'user_name',
        'user_email',
        'product_name',
        'model_sku',
        'price',
        'image_path',
        'tryon_mode',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // Một kết quả thử kính thuộc về một tài khoản khách hàng.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Một kết quả thử kính gắn với sản phẩm kính đã được chọn.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Biến thể giúp biết khách thử màu/size nào nếu sản phẩm có nhiều lựa chọn.
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Accessor đổi image_path trong database thành URL public để admin mở ảnh trực tiếp.
    public function getImageUrlAttribute(): string
    {
        // Ảnh mới lưu trực tiếp trong public/upload/tryons để dễ kiểm tra trong project.
        if (str_starts_with($this->image_path, 'upload/')) {
            return asset($this->image_path);
        }

        // Các ảnh cũ trước đó vẫn đọc được từ storage/app/public/tryons.
        return Storage::disk('public')->url($this->image_path);
    }
}
