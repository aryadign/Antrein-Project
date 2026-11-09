<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'business_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'properties',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Record a standardized audit event in the platform activity log.
     *
     * @param array<string, mixed> $properties
     */
    public static function log(
        string $action,
        string $description,
        Business|int|null $business = null,
        User|int|null $user = null,
        array $properties = []
    ): self {
        $userId = $user instanceof User ? $user->id : ($user ?? auth()->id());
        $businessId = $business instanceof Business ? $business->id : $business;

        return self::create([
            'user_id' => $userId,
            'business_id' => $businessId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }

    /**
     * Get the user that triggered this activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business context associated with this log.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
