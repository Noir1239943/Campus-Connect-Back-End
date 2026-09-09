<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'student_id' => $this->student_id,
            'email' => $this->email,
            'email_verified' => (bool) $this->email_verified_at,
            'role' => $this->role,
            'program' => $this->program,
            'year_level' => $this->year_level,
            'contact_number' => $this->contact_number,
            'preferences' => [
                'email' => (bool) $this->notify_email,
                'sms' => (bool) $this->notify_sms,
                'digest' => (bool) $this->notify_weekly_digest,
            ],
        ];
    }
}
