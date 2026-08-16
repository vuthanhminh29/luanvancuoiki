<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Gan ket qua xu ly vao bien $component.
        $component = Livewire::test(UpdateProfileInformationForm::class);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertEquals($user->name, $component->state['name']);
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertEquals($user->email, $component->state['email']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->actingAs($user = User::factory()->create());

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Livewire::test(UpdateProfileInformationForm::class)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->set('state', ['name' => 'Test Name', 'email' => 'test@example.com'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->call('updateProfileInformation');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertEquals('Test Name', $user->fresh()->name);
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertEquals('test@example.com', $user->fresh()->email);
    }
}
