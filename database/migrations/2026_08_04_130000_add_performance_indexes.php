<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'users' => [
            ['columns' => ['status'], 'name' => 'idx_users_status'],
            ['columns' => ['provider'], 'name' => 'idx_users_provider'],
        ],
        'password_reset_tokens' => [
            ['columns' => ['user_id', 'used_at', 'expires_at'], 'name' => 'idx_password_resets_user_used_expires'],
        ],
        'roles' => [
            ['columns' => ['code'], 'name' => 'idx_roles_code'],
        ],
        'user_roles' => [
            ['columns' => ['user_id', 'role_id'], 'name' => 'idx_user_roles_user_role'],
            ['columns' => ['role_id', 'user_id'], 'name' => 'idx_user_roles_role_user'],
        ],
        'orders' => [
            ['columns' => ['status', 'created_at'], 'name' => 'idx_orders_status_created'],
            ['columns' => ['payment_method', 'created_at'], 'name' => 'idx_orders_payment_created'],
            ['columns' => ['user_id', 'created_at'], 'name' => 'idx_orders_user_created'],
            ['columns' => ['order_code'], 'name' => 'idx_orders_order_code'],
            ['columns' => ['recipient_phone'], 'name' => 'idx_orders_recipient_phone'],
        ],
        'order_items' => [
            ['columns' => ['order_id'], 'name' => 'idx_order_items_order'],
            ['columns' => ['product_id'], 'name' => 'idx_order_items_product'],
            ['columns' => ['variant_id'], 'name' => 'idx_order_items_variant'],
            ['columns' => ['product_id', 'order_id'], 'name' => 'idx_order_items_product_order'],
        ],
        'products' => [
            ['columns' => ['status', 'created_at'], 'name' => 'idx_products_status_created'],
            ['columns' => ['status', 'category_id'], 'name' => 'idx_products_status_category'],
            ['columns' => ['status', 'brand_id'], 'name' => 'idx_products_status_brand'],
            ['columns' => ['status', 'view_count'], 'name' => 'idx_products_status_views'],
            ['columns' => ['slug'], 'name' => 'idx_products_slug'],
            ['columns' => ['product_code'], 'name' => 'idx_products_code'],
        ],
        'product_variants' => [
            ['columns' => ['product_id', 'status'], 'name' => 'idx_product_variants_product_status'],
            ['columns' => ['color_id'], 'name' => 'idx_product_variants_color'],
            ['columns' => ['lens_size_id'], 'name' => 'idx_product_variants_lens_size'],
            ['columns' => ['sku'], 'name' => 'idx_product_variants_sku'],
        ],
        'inventories' => [
            ['columns' => ['variant_id'], 'name' => 'idx_inventories_variant'],
            ['columns' => ['warehouse_id'], 'name' => 'idx_inventories_warehouse'],
            ['columns' => ['variant_id', 'warehouse_id'], 'name' => 'idx_inventories_variant_warehouse'],
        ],
        'categories' => [
            ['columns' => ['status'], 'name' => 'idx_categories_status'],
            ['columns' => ['slug'], 'name' => 'idx_categories_slug'],
        ],
        'brands' => [
            ['columns' => ['status'], 'name' => 'idx_brands_status'],
        ],
        'banners' => [
            ['columns' => ['status', 'position', 'priority'], 'name' => 'idx_banners_status_position_priority'],
            ['columns' => ['start_at', 'end_at'], 'name' => 'idx_banners_dates'],
        ],
        'posts' => [
            ['columns' => ['status', 'created_at'], 'name' => 'idx_posts_status_created'],
            ['columns' => ['slug'], 'name' => 'idx_posts_slug'],
        ],
        'product_reviews' => [
            ['columns' => ['product_id', 'status', 'created_at'], 'name' => 'idx_product_reviews_product_status_created'],
            ['columns' => ['user_id'], 'name' => 'idx_product_reviews_user'],
            ['columns' => ['order_item_id'], 'name' => 'idx_product_reviews_order_item'],
        ],
        'user_addresses' => [
            ['columns' => ['user_id', 'is_default', 'created_at'], 'name' => 'idx_user_addresses_user_default_created'],
        ],
        'try_on_snapshots' => [
            ['columns' => ['user_id', 'id'], 'name' => 'idx_tryon_snapshots_user_id'],
            ['columns' => ['product_id'], 'name' => 'idx_tryon_snapshots_product'],
        ],
        'stock_transactions' => [
            ['columns' => ['type', 'related_order_id'], 'name' => 'idx_stock_transactions_type_order'],
            ['columns' => ['transaction_code'], 'name' => 'idx_stock_transactions_code'],
        ],
        'stock_transaction_items' => [
            ['columns' => ['variant_id'], 'name' => 'idx_stock_transaction_items_variant'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->addIndexIfPossible($table, $index['columns'], $index['name']);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes) as $table => $indexes) {
            foreach (array_reverse($indexes) as $index) {
                $this->dropIndexIfExists($table, $index['name']);
            }
        }
    }

    private function addIndexIfPossible(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name) || Schema::hasIndex($table, $columns)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, fn (Blueprint $table) => $table->index($columns, $name));
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $table) => $table->dropIndex($name));
    }
};
