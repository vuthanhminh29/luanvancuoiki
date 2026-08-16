<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Http\Livewire\ApiTokenManager;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_permissions_can_be_updated(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::hasApiFeatures()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('API support is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        // Luong: Gan ket qua xu ly vao bien $token.
        $token = $user->tokens()->create([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'name' => 'Test Token',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'token' => Str::random(40),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'abilities' => ['create', 'read'],
        ]);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(ApiTokenManager::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set(['managingPermissionsFor' => $token])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set(['updateApiTokenForm' => [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'permissions' => [
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    'delete',
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    'missing-permission',
                ],
            ]])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('updateApiToken');

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->assertTrue($user->fresh()->tokens->first()->can('delete'));
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->assertFalse($user->fresh()->tokens->first()->can('read'));
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->assertFalse($user->fresh()->tokens->first()->can('missing-permission'));
    }
}
