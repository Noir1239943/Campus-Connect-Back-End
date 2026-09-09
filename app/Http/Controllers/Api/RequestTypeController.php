<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RequestTypeResource;
use App\Models\RequestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestTypes = RequestType::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $requestTypes->map(
                fn ($requestType) => (new RequestTypeResource($requestType))->resolve($request)
            )->values(),
        ]);
    }
}
