<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\CreatesStatusNotification;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    use CreatesStatusNotification;

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->serviceRequests()->with(['office', 'requestType']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('requestType', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('office', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $requests = $query->latest('updated_at')->get();

        return response()->json([
            'data' => $requests->map(
                fn ($serviceRequest) => (new ServiceRequestResource($serviceRequest))->resolve($request)
            )->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'request_type_id' => ['required', 'exists:request_types,id'],
            'office_id' => ['required', 'exists:offices,id'],
            'subject' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $path = $request->hasFile('attachment')
            ? $request->file('attachment')->store('attachments', 'public')
            : null;

        $serviceRequest = $request->user()->serviceRequests()->create([
            'office_id' => $data['office_id'],
            'request_type_id' => $data['request_type_id'],
            'subject' => $data['subject'],
            'details' => $data['details'],
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        $serviceRequest->load(['office', 'requestType']);

        return response()->json([
            'data' => (new ServiceRequestResource($serviceRequest))->resolve($request),
        ], 201);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        abort_unless($serviceRequest->user_id === $request->user()->id, 403);

        $serviceRequest->load(['office', 'requestType']);

        return response()->json([
            'data' => (new ServiceRequestResource($serviceRequest))->resolve($request),
        ]);
    }

    /**
     * Let a student withdraw their own request. This is intentionally the
     * only status change a student can make — moving a request to
     * in_review/completed/rejected is a staff decision and only reachable
     * through the admin ServiceRequestController, which is role-gated.
     */
    public function cancel(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        abort_unless($serviceRequest->user_id === $request->user()->id, 403);
        abort_unless($serviceRequest->status === 'pending', 422, 'Only pending requests can be cancelled.');

        $serviceRequest->update(['status' => 'cancelled']);
        $serviceRequest->load(['office', 'requestType']);

        $this->notifyStatusChange($serviceRequest);

        return response()->json([
            'data' => (new ServiceRequestResource($serviceRequest))->resolve($request),
        ]);
    }
}
