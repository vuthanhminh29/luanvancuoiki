<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 *
 * Factory này trước đây là bản scaffolding gốc của Jetstream: ghi vào các cột
 * name / password / remember_token / profile_photo_path / current_team_id và
 * import App\Models\Team. Không cột nào trong số đó tồn tại trong schema của dự án
 * (schema dùng full_name / password_hash), còn model Team thì không có, nên mọi
 * test dùng User::factory() đều chết. Đã viết lại cho khớp bảng users thật.
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Hash mật khẩu mặc định, tính một lần rồi dùng lại cho mọi user trong test.
     */
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'full_name' => fake()->name(),
            'phone' => '09' . fake()->numerify('########'),
            'provider' => 'LOCAL',
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
            'failed_login_count' => 0,
        ];
    }

    /**
     * User chưa xác thực email.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * User bị khóa: EnsureAdmin và AuthController đều chặn trạng thái này.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'LOCKED',
        ]);
    }
}
