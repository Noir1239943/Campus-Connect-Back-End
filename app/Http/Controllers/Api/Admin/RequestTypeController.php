<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RequestTypeResource;
use App\Models\RequestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequestTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestTypes = RequestType::orderBy('name')->get();

        return response()->json([
            'data' => $requestTypes->map(
                fn ($requestType) => (new RequestTypeResource($requestType))->resolve($request)
            )->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:request_types,name'],
        ]);

        $requestType = RequestType::create(['is_active' => true, ...$data]);

        return response()->json([
            'data' => (new RequestTypeResource($requestType))->resolve($request),
        ], 201);
    }

    public function update(Request $request, RequestType $requestType): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('request_types', 'name')->ignore($requestType->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $requestType->update($data);

        return response()->json([
            'data' => (new RequestTypeResource($requestType))->resolve($request),
        ]);
    }

    public function destroy(RequestType $requestType): JsonResponse
    {
        abort_if($requestType->serviceRequests()->exists(), 422, 'This request type has requests on file and cannot be deleted — disable it instead.');

        $requestType->delete();

        return response()->json(['message' => 'Request type deleted.']);
    }
}
