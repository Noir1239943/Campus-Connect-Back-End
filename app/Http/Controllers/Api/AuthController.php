<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Normalize before the unique:users,email check runs — otherwise
        // "Name@Gmail.com" passes as unique against an existing
        // "name@gmail.com" row (SQLite's default string comparison is
        // case-sensitive), creating a second account for the same address.
        if ($request->filled('email')) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['required', 'string', 'max:50', 'unique:users,student_id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'program' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('campusconnect')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required_without:email', 'string'],
            'email' => ['required_without:student_id', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = isset($data['student_id'])
            ? User::where('student_id', $data['student_id'])->first()
            : User::where('email', strtolower(trim($data['email'])))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('campusconnect')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
