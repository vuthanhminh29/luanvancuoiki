<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerEmailVerificationLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_logged_in_after_clicking_verification_link(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'user' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
