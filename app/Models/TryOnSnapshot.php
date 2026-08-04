<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TryOnSnapshot extends Model
{
    // CÃ¡c cá»™t Ä‘Æ°á»£c phÃ©p lÆ°u khi controller táº¡o káº¿t quáº£ thá»­ kÃ­nh má»›i.
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

    // Má»™t káº¿t quáº£ thá»­ kÃ­nh thuá»™c vá» má»™t tÃ i khoáº£n khÃ¡ch hÃ ng.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Má»™t káº¿t quáº£ thá»­ kÃ­nh gáº¯n vá»›i sáº£n pháº©m kÃ­nh Ä‘Ã£ Ä‘Æ°á»£c chá»n.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Biáº¿n thá»ƒ giÃºp biáº¿t khÃ¡ch thá»­ mÃ u/size nÃ o náº¿u sáº£n pháº©m cÃ³ nhiá»u lá»±a chá»n.
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Accessor Ä‘á»•i image_path trong database thÃ nh URL public Ä‘á»ƒ admin má»Ÿ áº£nh trá»±c tiáº¿p.
    public function getImageUrlAttribute(): string
    {
        // áº¢nh má»›i lÆ°u trá»±c tiáº¿p trong public/upload/tryons Ä‘á»ƒ dá»… kiá»ƒm tra trong project.
        if (str_starts_with($this->image_path, 'upload/')) {
            return asset($this->image_path);
        }

        // CÃ¡c áº£nh cÅ© trÆ°á»›c Ä‘Ã³ váº«n Ä‘á»c Ä‘Æ°á»£c tá»« storage/app/public/tryons.
        return Storage::disk('public')->url($this->image_path);
    }
}
