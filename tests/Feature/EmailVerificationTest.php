<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_sends_a_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Jane Student',
            'student_id' => '2024-00001',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        Notification::assertSentTo(
            User::where('email', 'jane@example.com')->firstOrFail(),
            VerifyEmail::class
        );
    }

    public function test_a_valid_signed_link_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($url);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_an_invalid_hash_does_not_verify_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('someone-else@example.com')]
        );

        $response = $this->getJson($url);

        $response->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->getJson("/api/email/verify/{$user->id}/".sha1($user->email));

        $response->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_unverified_student_cannot_file_a_request(): void
    {
        $user = User::factory()->unverified()->create();
        $office = Office::factory()->create();
        $requestType = RequestType::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/requests', [
            'request_type_id' => $requestType->id,
            'office_id' => $office->id,
            'subject' => 'Need a transcript',
            'details' => 'For a scholarship application.',
        ]);

        $response->assertForbidden();
    }

    public function test_a_verified_student_can_file_a_request(): void
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        $requestType = RequestType::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/requests', [
            'request_type_id' => $requestType->id,
            'office_id' => $office->id,
            'subject' => 'Need a transcript',
            'details' => 'For a scholarship application.',
        ]);

        $response->assertCreated();
    }
}
