<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): UserResource
    {
        $user = $request->user();

        if ($request->filled('email')) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'program' => ['sometimes', 'nullable', 'string', 'max:255'],
            'year_level' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'notify_email' => ['sometimes', 'boolean'],
            'notify_sms' => ['sometimes', 'boolean'],
            'notify_weekly_digest' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return new UserResource($user);
    }
}
