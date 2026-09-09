<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\CreatesStatusNotification;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    use CreatesStatusNotification;

    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::query()->with(['user', 'office', 'requestType']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('requestType', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('office', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
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

    public function updateStatus(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(ServiceRequest::STATUSES)],
        ]);

        $serviceRequest->update(['status' => $data['status']]);
        $serviceRequest->load(['user', 'office', 'requestType']);

        $this->notifyStatusChange($serviceRequest);

        return response()->json([
            'data' => (new ServiceRequestResource($serviceRequest))->resolve($request),
        ]);
    }
}
