<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    public const string STATUS_BUKA = 'buka';
    public const string STATUS_TUTUP = 'tutup';
    public const string STATUS_JEDA = 'jeda';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'logo_path',
        'description',
        'address',
        'phone',
        'timezone',
        'status',
        'is_suspended',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_suspended' => 'boolean',
        ];
    }

    /**
     * Determine whether the business is currently open for queue intake.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_BUKA && ! $this->is_suspended;
    }

    /**
     * Determine whether the business is temporarily paused.
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_JEDA;
    }

    /**
     * Determine whether the business is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_TUTUP || $this->is_suspended;
    }

    /**
     * Determine whether online queue intake is permitted.
     */
    public function canAcceptOnlineQueue(): bool
    {
        return $this->isOpen();
    }

    /**
     * Get the owning user account (merchant owner).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'category_id');
    }

    /**
     * Get the weekly operating hours.
     */
    public function hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    /**
     * Get the services offered by this business.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get daily queue sessions.
     */
    public function dailyQueues(): HasMany
    {
        return $this->hasMany(DailyQueue::class);
    }

    /**
     * Get all queue tickets across all sessions.
     */
    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * Get activity logs associated with this business.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}

