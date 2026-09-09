<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Complete verification from the signed link emailed by
     * AuthController::register(). Deliberately not behind auth:sanctum —
     * the frontend calls this straight from the emailed link, with no
     * bearer token available — so the "signed" route middleware (checking
     * the id/hash/expires/signature) is what proves the request is
     * legitimate instead.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['message' => 'Email verified.']);
    }

    /**
     * Let a signed-in but unverified user request a fresh link, for when
     * the original one expired or was lost.
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }
}
