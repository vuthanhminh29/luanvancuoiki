<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->withPersonalTeam()->create();

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->actingAs($user)->get('/user/confirm-password');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Gan ket qua xu ly vao bien $response.
        $response = $this->actingAs($user)->post('/user/confirm-password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => 'password',
        ]);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertRedirect();
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertSessionHasNoErrors();
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Gan ket qua xu ly vao bien $response.
        $response = $this->actingAs($user)->post('/user/confirm-password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => 'wrong-password',
        ]);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertSessionHasErrors();
    }
}
