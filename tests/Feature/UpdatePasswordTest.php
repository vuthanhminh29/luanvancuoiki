<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Http\Livewire\UpdatePasswordForm;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(UpdatePasswordForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('state', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'current_password' => 'password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password' => 'new-password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_confirmation' => 'new-password',
            ])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('updatePassword');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(UpdatePasswordForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('state', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'current_password' => 'wrong-password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password' => 'new-password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_confirmation' => 'new-password',
            ])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('updatePassword')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->assertHasErrors(['current_password']);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_passwords_must_match(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(UpdatePasswordForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('state', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'current_password' => 'password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password' => 'new-password',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_confirmation' => 'wrong-password',
            ])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('updatePassword')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->assertHasErrors(['password']);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
