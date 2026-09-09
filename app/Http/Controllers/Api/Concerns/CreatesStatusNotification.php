<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\ServiceRequest;

trait CreatesStatusNotification
{
    private function notifyStatusChange(ServiceRequest $serviceRequest): void
    {
        $serviceRequest->notifications()->create([
            'user_id' => $serviceRequest->user_id,
            'title' => $this->statusTitle($serviceRequest),
            'detail' => "{$serviceRequest->office->name} updated your request to \"{$this->statusLabel($serviceRequest->status)}\".",
            'tone' => $this->statusTone($serviceRequest->status),
        ]);
    }

    private function statusTitle(ServiceRequest $serviceRequest): string
    {
        return "{$serviceRequest->requestType->name} moved to {$this->statusLabel($serviceRequest->status)}";
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'in_review' => 'In Review',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => 'Pending',
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'rejected', 'cancelled' => 'danger',
            default => 'info',
        };
    }
}
