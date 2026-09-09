<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => 'REQ-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'type' => $this->requestType->name,
            'office' => $this->office->name,
            'status' => $this->status,
            'subject' => $this->subject,
            'description' => $this->details,
            'attachment_url' => $this->attachment_path ? Storage::url($this->attachment_path) : null,
            'submitted' => $this->created_at->format('Y-m-d'),
            'updated' => $this->updated_at->format('Y-m-d'),
            'requester' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'student_id' => $this->user->student_id,
            ]),
        ];
    }
}
