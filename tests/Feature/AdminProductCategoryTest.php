<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_them_san_pham_luu_duoc_nhieu_danh_muc(): void
    {
        $admin = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId([
            'code' => 'ADMIN',
            'name' => 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => $roleId,
        ]);

        $categoryIds = [
            $this->categoryId('Kinh mat'),
            $this->categoryId('Gong kinh'),
        ];

        $shapeId = DB::table('frame_shapes')->insertGetId([
            'code' => 'ROUND',
            'name' => 'Tron',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Kinh test nhieu danh muc',
            'category_ids' => $categoryIds,
            'frame_shape_id' => $shapeId,
            'uv_protection' => 'UV400',
            'import_price' => 100000,
            'base_price' => 150000,
            'sale_price' => null,
            'status' => 'ACTIVE',
            'description' => 'San pham test',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Kinh test nhieu danh muc')->firstOrFail();

        $this->assertSame($categoryIds[0], (int) $product->category_id);
        $this->assertEqualsCanonicalizing(
            $categoryIds,
            DB::table('category_product')
                ->where('product_id', $product->id)
                ->pluck('category_id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );
    }

    private function categoryId(string $name): int
    {
        return DB::table('categories')->insertGetId([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
