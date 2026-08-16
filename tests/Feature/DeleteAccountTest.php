<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Http\Livewire\DeleteUserForm;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accounts_can_be_deleted(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::hasAccountDeletionFeatures()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Account deletion is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Gan ket qua xu ly vao bien $component.
        $component = Livewire::test(DeleteUserForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('password', 'password')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('deleteUser');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_before_account_can_be_deleted(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::hasAccountDeletionFeatures()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Account deletion is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(DeleteUserForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('password', 'wrong-password')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('deleteUser')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->assertHasErrors(['password']);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertNotNull($user->fresh());
    }
}
