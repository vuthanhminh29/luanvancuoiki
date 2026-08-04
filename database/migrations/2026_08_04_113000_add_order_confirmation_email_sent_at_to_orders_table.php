<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cá»™t nÃ y giÃºp há»‡ thá»‘ng biáº¿t Ä‘Æ¡n nÃ o Ä‘Ã£ gá»­i email xÃ¡c nháº­n thÃ nh cÃ´ng.
        // Nháº¥t lÃ  VNPay cÃ³ cáº£ return URL vÃ  IPN, náº¿u khÃ´ng Ä‘Ã¡nh dáº¥u thÃ¬ dá»… gá»­i email trÃ¹ng.
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'order_confirmation_email_sent_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('order_confirmation_email_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        // Khi rollback chá»‰ xÃ³a cá»™t Ä‘Ã¡nh dáº¥u email, khÃ´ng Ä‘á»¥ng tá»›i dá»¯ liá»‡u Ä‘Æ¡n hÃ ng khÃ¡c.
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_confirmation_email_sent_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('order_confirmation_email_sent_at');
        });
    }
};
