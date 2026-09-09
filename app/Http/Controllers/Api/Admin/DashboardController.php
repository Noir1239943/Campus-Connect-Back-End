<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $counts = ServiceRequest::query()
            ->selectRaw(
                'count(*) as total, '.
                "sum(case when status in ('pending', 'in_review') then 1 else 0 end) as awaiting, ".
                "sum(case when status = 'completed' then 1 else 0 end) as completed"
            )
            ->first();

        $recent = ServiceRequest::query()
            ->with(['user', 'office', 'requestType'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'total' => (int) $counts->total,
                'awaiting' => (int) $counts->awaiting,
                'completed' => (int) $counts->completed,
                'total_users' => User::query()->count(),
            ],
            'recent_requests' => [
                'data' => $recent->map(
                    fn ($serviceRequest) => (new ServiceRequestResource($serviceRequest))->resolve($request)
                )->values(),
            ],
        ]);
    }
}
