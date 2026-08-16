<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->get('/login');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Gan ket qua xu ly vao bien $response.
        $response = $this->post('/login', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $user->email,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => 'password',
        ]);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertAuthenticated();
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->post('/login', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $user->email,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => 'wrong-password',
        ]);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertGuest();
    }
}
