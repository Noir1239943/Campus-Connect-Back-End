<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $offices = Office::orderBy('name')->get();

        return response()->json([
            'data' => $offices->map(fn ($office) => (new OfficeResource($office))->resolve($request))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:offices,name'],
        ]);

        $office = Office::create(['is_active' => true, ...$data]);

        return response()->json([
            'data' => (new OfficeResource($office))->resolve($request),
        ], 201);
    }

    public function update(Request $request, Office $office): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('offices', 'name')->ignore($office->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $office->update($data);

        return response()->json([
            'data' => (new OfficeResource($office))->resolve($request),
        ]);
    }

    public function destroy(Office $office): JsonResponse
    {
        abort_if($office->serviceRequests()->exists(), 422, 'This office has requests on file and cannot be deleted — disable it instead.');

        $office->delete();

        return response()->json(['message' => 'Office deleted.']);
    }
}
