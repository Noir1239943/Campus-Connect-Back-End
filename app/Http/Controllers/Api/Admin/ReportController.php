<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $byStatus = ServiceRequest::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $byOffice = ServiceRequest::query()
            ->join('offices', 'offices.id', '=', 'service_requests.office_id')
            ->select('offices.name', DB::raw('count(*) as count'))
            ->groupBy('offices.name')
            ->orderByDesc('count')
            ->get();

        $byType = ServiceRequest::query()
            ->join('request_types', 'request_types.id', '=', 'service_requests.request_type_id')
            ->select('request_types.name', DB::raw('count(*) as count'))
            ->groupBy('request_types.name')
            ->orderByDesc('count')
            ->get();

        $byWeek = ServiceRequest::query()
            ->select(DB::raw("strftime('%Y-%W', created_at) as week"), DB::raw('count(*) as count'))
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        return response()->json([
            'data' => [
                'by_status' => $byStatus,
                'by_office' => $byOffice,
                'by_type' => $byType,
                'by_week' => $byWeek,
            ],
        ]);
    }
}
