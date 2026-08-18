<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_filtered_by_secondary_category(): void
    {
        $primary = Category::create([
            'name' => 'Kính mát',
            'slug' => 'kinh-mat',
            'status' => 'ACTIVE',
        ]);

        $secondary = Category::create([
            'name' => 'Ray-Ban',
            'slug' => 'ray-ban',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'product_code' => 'SPTEST001',
            'name' => 'Ray-Ban Test',
            'slug' => 'ray-ban-test',
            'category_id' => $primary->id,
            'base_price' => 1000000,
            'status' => 'ACTIVE',
            'view_count' => 0,
        ]);

        $product->categories()->sync([$primary->id, $secondary->id]);

        $this->assertTrue(Product::inCategories([$secondary->id])->whereKey($product->id)->exists());
        $this->assertSame(1, (int) Category::withCount('products')->find($secondary->id)->products_count);
    }
}
