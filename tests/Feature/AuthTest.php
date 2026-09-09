<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Student',
            'student_id' => '2024-00001',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user']);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'student']);
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_an_incorrect_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_email_is_stored_lowercase_regardless_of_input_case(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Student',
            'student_id' => '2024-00001',
            'email' => 'Jane@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'Jane@Example.com']);
    }

    public function test_registering_with_a_different_case_of_an_existing_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Impersonator',
            'student_id' => '2024-99999',
            'email' => 'Jane@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_login_matches_email_regardless_of_case(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'password' => Hash::make('correct-password')]);

        $response = $this->postJson('/api/login', [
            'email' => 'Jane@Example.com',
            'password' => 'correct-password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.id', $user->id);
    }
}
