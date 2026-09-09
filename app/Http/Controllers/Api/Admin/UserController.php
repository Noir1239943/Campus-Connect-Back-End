<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->get();

        return response()->json([
            'data' => $users->map(fn ($user) => (new UserResource($user))->resolve($request))->values(),
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own role.');

        $data = $request->validate([
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $user->update(['role' => $data['role']]);

        return response()->json([
            'data' => (new UserResource($user))->resolve($request),
        ]);
    }
}
