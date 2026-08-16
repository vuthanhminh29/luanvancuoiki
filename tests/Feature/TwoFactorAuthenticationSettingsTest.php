<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorAuthenticationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_authentication_can_be_enabled(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::canManageTwoFactorAuthentication()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->withSession(['auth.password_confirmed_at' => time()]);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(TwoFactorAuthenticationForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('enableTwoFactorAuthentication');

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = $user->fresh();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertNotNull($user->two_factor_secret);
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertCount(8, $user->recoveryCodes());
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::canManageTwoFactorAuthentication()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->withSession(['auth.password_confirmed_at' => time()]);

        // Luong: Gan ket qua xu ly vao bien $component.
        $component = Livewire::test(TwoFactorAuthenticationForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('enableTwoFactorAuthentication')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('regenerateRecoveryCodes');

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = $user->fresh();

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $component->call('regenerateRecoveryCodes');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertCount(8, $user->recoveryCodes());
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertCount(8, array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()));
    }

    public function test_two_factor_authentication_can_be_disabled(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::canManageTwoFactorAuthentication()) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->withSession(['auth.password_confirmed_at' => time()]);

        // Luong: Gan ket qua xu ly vao bien $component.
        $component = Livewire::test(TwoFactorAuthenticationForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('enableTwoFactorAuthentication');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertNotNull($user->fresh()->two_factor_secret);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $component->call('disableTwoFactorAuthentication');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertNull($user->fresh()->two_factor_secret);
    }
}
