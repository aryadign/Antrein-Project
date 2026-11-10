<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * The "booted" method of the model.
     * Ensures that when a business is created with a user_id, a matching owner membership is created.
     */
    protected static function booted(): void
    {
        static::created(function (Business $business) {
            if ($business->user_id) {
                $connection = $business->getConnectionName();
                $membership = null;
                try {
                    if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('business_memberships')) {
                        $membership = BusinessMembership::on($connection)->firstOrCreate(
                            ['user_id' => $business->user_id],
                            [
                                'business_id' => $business->id,
                                'role' => BusinessMembership::ROLE_OWNER,
                                'is_active' => true,
                                'joined_at' => $business->created_at ?? now(),
                            ]
                        );
                    }

                    if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('business_counters')) {
                        $counter = BusinessCounter::on($connection)->firstOrCreate(
                            ['business_id' => $business->id, 'code' => 'L-01'],
                            [
                                'name' => 'Loket 1',
                                'description' => 'Loket pelayanan utama',
                                'is_active' => true,
                            ]
                        );

                        if ($membership && \Illuminate\Support\Facades\Schema::connection($connection)->hasTable('counter_operator_assignments')) {
                            $counter->assignedMemberships()->syncWithoutDetaching([$membership->id]);
                        }
                    }
                } catch (\Throwable) {
                    // Suppress if table does not exist on target connection during isolated DB tests
                }
            }
        });

        static::updated(function (Business $business) {
            if ($business->wasChanged('user_id') && $business->user_id) {
                $connection = $business->getConnectionName();
                try {
                    if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('business_memberships')) {
                        BusinessMembership::on($connection)->updateOrCreate(
                            ['user_id' => $business->user_id],
                            [
                                'business_id' => $business->id,
                                'role' => BusinessMembership::ROLE_OWNER,
                                'is_active' => true,
                                'joined_at' => now(),
                            ]
                        );
                    }
                } catch (\Throwable) {
                    // Suppress if table does not exist on target connection during isolated DB tests
                }
            }
        });
    }

    /**
     * Get the public URL for the business logo.
     */
    public function getLogoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
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

    /**
     * Get all memberships belonging to this business.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessMembership::class);
    }

    /**
     * Get all member users belonging to this business.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_memberships')
            ->withPivot(['role', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get all staff operator memberships.
     */
    public function staffMemberships(): HasMany
    {
        return $this->memberships()->where('role', BusinessMembership::ROLE_OPERATOR);
    }

    /**
     * Get all staff invitations for this business.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(StaffInvitation::class);
    }

    /**
     * Get pending staff invitations that have not been accepted or revoked and are not expired.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Get the counters belonging to this business.
     */
    public function counters(): HasMany
    {
        return $this->hasMany(BusinessCounter::class);
    }

    /**
     * Get the active counters belonging to this business.
     */
    public function activeCounters(): HasMany
    {
        return $this->hasMany(BusinessCounter::class)->where('is_active', true);
    }

    /**
     * Determine whether a user is an active owner of this business.
     */
    public function isOwner(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('role', BusinessMembership::ROLE_OWNER)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine whether a user is an active member (owner or operator) of this business.
     */
    public function isMember(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}

