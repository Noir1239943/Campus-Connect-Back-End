<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_emails_a_reset_token_for_a_known_account(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $response->assertOk();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_gives_the_same_response_for_an_unknown_account(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertOk();
        Notification::assertNothingSent();
    }

    public function test_a_valid_token_resets_the_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_knowing_only_the_student_id_or_email_cannot_reset_a_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'guessed-or-missing-token',
            'email' => $user->email,
            'password' => 'takeover-password',
            'password_confirmation' => 'takeover-password',
        ]);

        $response->assertUnprocessable();
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
