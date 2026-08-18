<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('role_code', 50)->nullable()->unique();
                $table->string('code', 50)->nullable()->unique();
                $table->string('role_name', 100)->nullable();
                $table->string('name', 100)->nullable();
                $table->string('description')->nullable();
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->foreignId('user_id');
                $table->foreignId('role_id');
                $table->primary(['user_id', 'role_id']);
            });
        }

        if (!Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('recipient_name', 100)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('province_code', 20)->nullable();
                $table->string('province_name', 100)->nullable();
                $table->string('district_code', 20)->nullable();
                $table->string('district_name', 100)->nullable();
                $table->string('ward_code', 20)->nullable();
                $table->string('ward_name', 100)->nullable();
                $table->string('address_detail')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable();
                $table->string('name', 100);
                $table->string('slug', 120)->nullable();
                $table->string('description')->nullable();
                $table->string('image_url')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120)->nullable();
                $table->string('logo_url')->nullable();
                $table->string('description')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('frame_shapes')) {
            Schema::create('frame_shapes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name', 50);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('frame_materials')) {
            Schema::create('frame_materials', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name', 50);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('colors')) {
            Schema::create('colors', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->string('code', 20)->nullable();
                $table->string('color_code', 20)->nullable();
                $table->string('hex_code', 20)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lens_sizes')) {
            Schema::create('lens_sizes', function (Blueprint $table) {
                $table->id();
                $table->string('size_label', 50)->nullable();
                $table->string('name', 50)->nullable();
                $table->integer('bridge_width')->nullable();
                $table->integer('temple_length')->nullable();
                $table->integer('lens_width')->nullable();
                $table->integer('lens_height')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('product_code', 50)->nullable();
                $table->string('name', 200);
                $table->string('slug', 220)->nullable();
                $table->foreignId('category_id')->nullable();
                $table->foreignId('brand_id')->nullable();
                $table->foreignId('frame_shape_id')->nullable();
                $table->foreignId('frame_material_id')->nullable();
                $table->string('uv_protection', 50)->nullable();
                $table->decimal('import_price', 15, 2)->default(0);
                $table->decimal('base_price', 15, 2)->default(0);
                $table->decimal('sale_price', 15, 2)->nullable();
                $table->text('description')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->string('image_url')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->unsignedInteger('view_count')->default(0);
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable();
                $table->string('sku', 50)->nullable();
                $table->foreignId('color_id')->nullable();
                $table->foreignId('lens_size_id')->nullable();
                $table->decimal('variant_price', 15, 2)->nullable();
                $table->string('image_url')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable();
                $table->foreignId('variant_id')->nullable();
                $table->string('image_url');
                $table->string('alt_text')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_thumbnail')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('warehouse_code', 50)->nullable();
                $table->string('code', 50)->nullable();
                $table->string('name', 100);
                $table->string('type', 20)->default('NORMAL');
                $table->unsignedInteger('capacity')->default(10000);
                $table->string('province_code', 20)->nullable();
                $table->string('province_name', 100)->nullable();
                $table->string('district_code', 20)->nullable();
                $table->string('district_name', 100)->nullable();
                $table->string('ward_code', 20)->nullable();
                $table->string('ward_name', 100)->nullable();
                $table->string('address_detail')->nullable();
                $table->unsignedInteger('min_stock_level')->default(10);
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventories')) {
            Schema::create('inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->nullable();
                $table->foreignId('variant_id')->nullable();
                $table->integer('quantity')->default(0);
                $table->unsignedInteger('min_stock_level')->default(10);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_code', 50)->nullable();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('address_id')->nullable();
                $table->string('recipient_name', 100)->nullable();
                $table->string('recipient_phone', 20)->nullable();
                $table->string('customer_name', 100)->nullable();
                $table->string('customer_phone', 20)->nullable();
                $table->string('shipping_address')->nullable();
                $table->string('payment_method', 20)->default('COD');
                $table->string('payment_status', 20)->default('PENDING');
                $table->string('status', 20)->default('PENDING');
                $table->decimal('subtotal_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('shipping_fee', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->foreignId('promotion_id')->nullable();
                $table->text('note')->nullable();
                $table->string('cancelled_reason')->nullable();
                $table->foreignId('confirmed_by')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable();
                $table->foreignId('product_id')->nullable();
                $table->foreignId('variant_id')->nullable();
                $table->string('product_name', 200)->nullable();
                $table->string('sku', 50)->nullable();
                $table->string('color_name', 50)->nullable();
                $table->string('lens_size_name', 50)->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('return_reasons')) {
            Schema::create('return_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name', 100);
                $table->string('type', 20)->default('RETURN');
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('return_requests')) {
            Schema::create('return_requests', function (Blueprint $table) {
                $table->id();
                $table->string('return_code', 50)->nullable();
                $table->foreignId('order_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->string('return_type', 20)->default('RETURN');
                $table->string('type', 20)->nullable()->default('RETURN');
                $table->foreignId('reason_id')->nullable();
                $table->string('reason_type', 50)->nullable();
                $table->text('reason_detail')->nullable();
                $table->string('status', 20)->default('PENDING');
                $table->text('admin_note')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->foreignId('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('return_request_items')) {
            Schema::create('return_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('return_request_id')->nullable();
                $table->foreignId('order_item_id')->nullable();
                $table->integer('quantity')->default(1);
                $table->foreignId('exchange_variant_id')->nullable();
                $table->text('condition_note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_transactions')) {
            Schema::create('stock_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_code', 50)->nullable();
                $table->string('type', 30)->nullable();
                $table->foreignId('warehouse_id')->nullable();
                $table->foreignId('source_warehouse_id')->nullable();
                $table->foreignId('target_warehouse_id')->nullable();
                $table->foreignId('related_order_id')->nullable();
                $table->string('status', 20)->default('COMPLETED');
                $table->date('expected_date')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('confirmed_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stock_transaction_items')) {
            Schema::create('stock_transaction_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transaction_id')->nullable();
                $table->foreignId('variant_id')->nullable();
                $table->integer('ordered_quantity')->default(0);
                $table->integer('actual_quantity')->default(0);
                $table->decimal('unit_cost', 15, 2)->nullable()->default(0);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('home_layouts')) {
            Schema::create('home_layouts', function (Blueprint $table) {
                $table->id();
                $table->string('section_key', 50)->nullable();
                $table->string('section_name', 100)->nullable();
                $table->string('section_code', 50)->nullable();
                $table->string('title', 100)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('post_categories')) {
            Schema::create('post_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120)->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable();
                $table->string('title', 200);
                $table->string('slug', 220)->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->text('summary')->nullable();
                $table->longText('content')->nullable();
                $table->string('status', 20)->default('PUBLISHED');
                $table->foreignId('created_by')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title', 100)->nullable();
                $table->string('image_url');
                $table->string('link_url')->nullable();
                $table->string('platform', 20)->default('BOTH');
                $table->string('position', 50)->default('HOME_BANNER_1');
                $table->integer('priority')->default(0);
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->boolean('is_visible')->default(true);
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->string('promotion_code', 50)->nullable();
                $table->string('code', 50)->nullable();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->string('discount_type', 20)->default('PERCENT');
                $table->decimal('discount_value', 15, 2)->default(0);
                $table->decimal('max_discount_amount', 15, 2)->nullable();
                $table->decimal('min_order_amount', 15, 2)->default(0);
                $table->string('scope', 20)->default('ORDER');
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_per_user')->nullable()->default(1);
                $table->integer('used_count')->default(0);
                $table->boolean('stackable')->default(false);
                $table->string('status', 20)->default('ACTIVE');
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
    }
};
