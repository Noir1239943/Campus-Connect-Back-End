<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Look up the account and, if one matches, email it a signed reset
     * link (see AppServiceProvider::boot(), which points the link at the
     * SPA's own /reset-password route). The response is identical whether
     * or not an account was found, so this endpoint can't be used to
     * confirm which student IDs or emails are registered.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required_without:email', 'string'],
            'email' => ['required_without:student_id', 'email'],
        ]);

        $user = isset($data['student_id'])
            ? User::where('student_id', $data['student_id'])->first()
            : User::where('email', strtolower(trim($data['email'])))->first();

        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return response()->json([
            'message' => 'If an account matches that Student ID or email, a password reset link has been sent.',
        ]);
    }

    /**
     * Complete a reset using the token emailed by forgotPassword(). Unlike
     * the old implementation, this only succeeds when the token is valid
     * for the given email, so knowing a student's ID or email is no longer
     * enough to take over their account.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [...$data, 'email' => strtolower(trim($data['email']))],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [match ($status) {
                    Password::INVALID_USER => 'We could not find an account matching that email.',
                    Password::RESET_THROTTLED => 'Please wait before retrying.',
                    default => 'This password reset link is invalid or has expired.',
                }],
            ]);
        }

        return response()->json(['message' => 'Password reset.']);
    }
}
