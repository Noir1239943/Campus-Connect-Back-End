<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_cannot_view_another_students_request(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->getJson("/api/requests/{$serviceRequest->id}");

        $response->assertForbidden();
    }

    public function test_a_student_can_view_their_own_request(): void
    {
        $owner = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->getJson("/api/requests/{$serviceRequest->id}");

        $response->assertOk();
    }

    public function test_a_student_can_cancel_their_own_pending_request(): void
    {
        $owner = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        $response = $this->actingAs($owner)->patchJson("/api/requests/{$serviceRequest->id}/cancel");

        $response->assertOk();
        $this->assertSame('cancelled', $serviceRequest->fresh()->status);
    }

    public function test_a_student_cannot_cancel_another_students_request(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        $response = $this->actingAs($intruder)->patchJson("/api/requests/{$serviceRequest->id}/cancel");

        $response->assertForbidden();
        $this->assertSame('pending', $serviceRequest->fresh()->status);
    }

    public function test_a_student_cannot_cancel_a_request_that_is_already_in_review(): void
    {
        $owner = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id, 'status' => 'in_review']);

        $response = $this->actingAs($owner)->patchJson("/api/requests/{$serviceRequest->id}/cancel");

        $response->assertUnprocessable();
        $this->assertSame('in_review', $serviceRequest->fresh()->status);
    }

    public function test_a_student_cannot_self_approve_their_own_request(): void
    {
        $owner = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        $response = $this->actingAs($owner)->patchJson("/api/requests/{$serviceRequest->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertNotFound();
        $this->assertSame('pending', $serviceRequest->fresh()->status);
    }

    public function test_a_student_cannot_reach_the_admin_status_update_route(): void
    {
        $owner = User::factory()->create(['role' => 'student']);
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        $response = $this->actingAs($owner)->patchJson("/api/admin/requests/{$serviceRequest->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertForbidden();
        $this->assertSame('pending', $serviceRequest->fresh()->status);
    }

    public function test_staff_can_approve_a_students_request_through_the_admin_route(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $owner = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        $response = $this->actingAs($staff)->patchJson("/api/admin/requests/{$serviceRequest->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertOk();
        $this->assertSame('completed', $serviceRequest->fresh()->status);
    }
}
