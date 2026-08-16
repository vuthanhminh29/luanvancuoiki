<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::registration())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Registration support is not enabled.');
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->get('/register');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (Features::enabled(Features::registration())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Registration support is enabled.');
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->get('/register');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::registration())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Registration support is not enabled.');
        }

        // Luong: Gan ket qua xu ly vao bien $response.
        $response = $this->post('/register', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'name' => 'Test User',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => 'test@example.com',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => 'password',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password_confirmation' => 'password',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertAuthenticated();
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
