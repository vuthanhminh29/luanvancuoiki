<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Http\Livewire\ApiTokenManager;
use Livewire\Livewire;
use Tests\TestCase;

class CreateApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_tokens_can_be_created(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::hasApiFeatures()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('API support is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(ApiTokenManager::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set(['createApiTokenForm' => [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Test Token',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'permissions' => [
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    'read',
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    'update',
                ],
            ]])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('createApiToken');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertCount(1, $user->fresh()->tokens);
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->assertEquals('Test Token', $user->fresh()->tokens->first()->name);
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->assertTrue($user->fresh()->tokens->first()->can('read'));
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->assertFalse($user->fresh()->tokens->first()->can('delete'));
    }
}
