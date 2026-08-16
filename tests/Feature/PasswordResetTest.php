<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::resetPasswords())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Password updates are not enabled.');
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->get('/forgot-password');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::resetPasswords())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Password updates are not enabled.');
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Notification::fake();

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->post('/forgot-password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $user->email,
        ]);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::resetPasswords())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Password updates are not enabled.');
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Notification::fake();

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->post('/forgot-password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $user->email,
        ]);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Notification::assertSentTo($user, ResetPassword::class, function (object $notification) {
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            $response = $this->get('/reset-password/'.$notification->token);

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $response->assertStatus(200);

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::resetPasswords())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Password updates are not enabled.');
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Notification::fake();

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->create();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->post('/forgot-password', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => $user->email,
        ]);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user) {
            // Luong: Gan ket qua xu ly vao bien $response.
            $response = $this->post('/reset-password', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'token' => $notification->token,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email' => $user->email,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password' => 'password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_confirmation' => 'password',
            ]);

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $response->assertSessionHasNoErrors();

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return true;
        });
    }
}
