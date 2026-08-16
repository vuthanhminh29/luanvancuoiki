<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::emailVerification())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Email verification not enabled.');
        }

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->withPersonalTeam()->unverified()->create();

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->actingAs($user)->get('/email/verify');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::emailVerification())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Email verification not enabled.');
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Event::fake();

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->unverified()->create();

        // Luong: Gan ket qua xu ly vao bien $verificationUrl.
        $verificationUrl = URL::temporarySignedRoute(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'verification.verify',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            now()->addMinutes(60),
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->actingAs($user)->get($verificationUrl);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Event::assertDispatched(Verified::class);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_can_not_verified_with_invalid_hash(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Features::enabled(Features::emailVerification())) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->markTestSkipped('Email verification not enabled.');
        }

        // Luong: Gan ket qua xu ly vao bien $user.
        $user = User::factory()->unverified()->create();

        // Luong: Gan ket qua xu ly vao bien $verificationUrl.
        $verificationUrl = URL::temporarySignedRoute(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'verification.verify',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            now()->addMinutes(60),
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $this->actingAs($user)->get($verificationUrl);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
