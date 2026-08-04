<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Báº£ng nÃ y lÆ°u má»—i láº§n khÃ¡ch báº¥m chá»¥p/lÆ°u káº¿t quáº£ sau khi thá»­ kÃ­nh.
        Schema::create('try_on_snapshots', function (Blueprint $table) {
            $table->id();
            // DB hiá»‡n táº¡i Ä‘ang dÃ¹ng bigint signed, nÃªn dÃ¹ng bigInteger + index Ä‘á»ƒ tÆ°Æ¡ng thÃ­ch.
            $table->bigInteger('user_id')->nullable()->index();
            $table->bigInteger('product_id')->nullable()->index();
            $table->bigInteger('variant_id')->nullable()->index();
            // LÆ°u láº¡i tÃªn/email táº¡i thá»i Ä‘iá»ƒm chá»¥p Ä‘á»ƒ sau nÃ y user Ä‘á»•i thÃ´ng tin váº«n xem Ä‘Æ°á»£c lá»‹ch sá»­ cÅ©.
            $table->string('user_name', 100);
            $table->string('user_email');
            // product_name vÃ  model_sku lÃ  thÃ´ng tin kÃ­nh Ä‘Ã£ thá»­, model_sku chÃ­nh lÃ  mÃ£ model Jeeliz.
            $table->string('product_name');
            $table->string('model_sku', 100);
            $table->decimal('price', 12, 2)->default(0);
            // áº¢nh tháº­t lÆ°u trong storage/app/public, database chá»‰ lÆ°u Ä‘Æ°á»ng dáº«n áº£nh.
            $table->string('image_path');
            $table->string('tryon_mode', 20)->default('camera');
            $table->timestamps();

            $table->index(['user_email', 'created_at']);
            $table->index(['model_sku', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('try_on_snapshots');
    }
};
