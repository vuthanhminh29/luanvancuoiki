<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'name' => fake()->name(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email' => fake()->unique()->safeEmail(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email_verified_at' => now(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'password' => static::$password ??= Hash::make('password'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'two_factor_secret' => null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'two_factor_recovery_codes' => null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'remember_token' => Str::random(10),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'profile_photo_path' => null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'current_team_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->state(fn (array $attributes) => [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user should have a personal team.
     */
    public function withPersonalTeam(?callable $callback = null): static
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::hasTeamFeatures()) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $this->state([]);
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->has(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            Team::factory()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->state(fn (array $attributes, User $user) => [
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'name' => $user->name.'\'s Team',
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'user_id' => $user->id,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'personal_team' => true,
                ])
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->when(is_callable($callback), $callback),
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'ownedTeams'
        );
    }
}
