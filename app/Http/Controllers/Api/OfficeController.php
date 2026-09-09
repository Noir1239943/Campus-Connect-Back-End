<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $offices = Office::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $offices->map(fn ($office) => (new OfficeResource($office))->resolve($request))->values(),
        ]);
    }
}
