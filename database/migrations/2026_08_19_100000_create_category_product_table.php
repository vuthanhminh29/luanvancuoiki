<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();

                $table->unique(['category_id', 'product_id'], 'category_product_unique');
                $table->index(['product_id', 'category_id'], 'category_product_product_category_index');
            });
        } else {
            $this->ensureIndexes();
        }

        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'category_id')) {
            return;
        }

        DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereNotNull('products.category_id')
            ->orderBy('products.id')
            ->select('products.id as product_id', 'products.category_id')
            ->chunk(500, function ($products): void {
                $now = now();

                $rows = $products->map(fn ($product): array => [
                    'category_id' => (int) $product->category_id,
                    'product_id' => (int) $product->product_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('category_product')->upsert(
                    $rows,
                    ['category_id', 'product_id'],
                    ['updated_at']
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }

    private function ensureIndexes(): void
    {
        if (! $this->indexExists('category_product_unique')) {
            Schema::table('category_product', function (Blueprint $table): void {
                $table->unique(['category_id', 'product_id'], 'category_product_unique');
            });
        }

        if (! $this->indexExists('category_product_product_category_index')) {
            Schema::table('category_product', function (Blueprint $table): void {
                $table->index(['product_id', 'category_id'], 'category_product_product_category_index');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            return collect($connection->select("PRAGMA index_list('category_product')"))
                ->contains(fn ($index): bool => ($index->name ?? null) === $indexName);
        }

        return collect($connection->select('SHOW INDEX FROM category_product WHERE Key_name = ?', [$indexName]))
            ->isNotEmpty();
    }
};
