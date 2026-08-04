<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Má»™t sá»‘ mÃ´i trÆ°á»ng test/schema cÅ© cÃ³ thá»ƒ chÆ°a táº¡o báº£ng orders.
        // Kiá»ƒm tra trÆ°á»›c Ä‘á»ƒ migration khÃ´ng lÃ m há»ng quÃ¡ trÃ¬nh cháº¡y lá»‡nh.
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            // LÆ°u hash cá»§a token xÃ¡c nháº­n, khÃ´ng lÆ°u token tháº­t.
            // Token tháº­t chá»‰ náº±m trong email gá»­i cho khÃ¡ch.
            if (! Schema::hasColumn('orders', 'cancel_confirmation_token_hash')) {
                $table->string('cancel_confirmation_token_hash', 64)->nullable();
            }

            // LÆ°u lÃ½ do admin nháº­p khi yÃªu cáº§u há»§y Ä‘á»ƒ Ä‘Æ°a vÃ o email vÃ  note Ä‘Æ¡n hÃ ng.
            if (! Schema::hasColumn('orders', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable();
            }

            // Thá»i Ä‘iá»ƒm admin gá»­i yÃªu cáº§u há»§y, dÃ¹ng Ä‘á»ƒ kiá»ƒm tra link háº¿t háº¡n sau 3 ngÃ y.
            if (! Schema::hasColumn('orders', 'cancel_requested_at')) {
                $table->timestamp('cancel_requested_at')->nullable();
            }

            // Thá»i Ä‘iá»ƒm khÃ¡ch báº¥m xÃ¡c nháº­n há»§y thÃ nh cÃ´ng.
            if (! Schema::hasColumn('orders', 'cancel_confirmed_at')) {
                $table->timestamp('cancel_confirmed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Rollback chá»‰ xÃ³a cÃ¡c cá»™t cá»§a luá»“ng xÃ¡c nháº­n há»§y.
        // CÃ¡c cá»™t khÃ¡c cá»§a orders Ä‘Æ°á»£c giá»¯ nguyÃªn.
        if (! Schema::hasTable('orders')) {
            return;
        }

        $columns = array_values(array_filter([
            'cancel_confirmation_token_hash',
            'cancel_reason',
            'cancel_requested_at',
            'cancel_confirmed_at',
        ], fn (string $column): bool => Schema::hasColumn('orders', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
