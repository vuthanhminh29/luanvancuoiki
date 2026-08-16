<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm;
use Livewire\Livewire;
use Tests\TestCase;

class BrowserSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_browser_sessions_can_be_logged_out(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs(User::factory()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(LogoutOtherBrowserSessionsForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('password', 'password')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('logoutOtherBrowserSessions')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->assertSuccessful();
    }
}
