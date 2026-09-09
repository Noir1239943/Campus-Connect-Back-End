<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'in_review', 'completed', 'rejected', 'cancelled'];

    protected $fillable = [
        'user_id',
        'office_id',
        'request_type_id',
        'subject',
        'details',
        'attachment_path',
        'status',
    ];

    /**
     * Lets routes accept the "REQ-0011" ids the frontend displays and links
     * with, alongside the raw numeric primary key.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === null && is_string($value) && str_starts_with($value, 'REQ-')) {
            $value = (int) substr($value, 4);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return BelongsTo<RequestType, $this>
     */
    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    /**
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
