<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    public const string STATUS_BUKA = 'buka';

    public const string STATUS_TUTUP = 'tutup';

    public const string STATUS_JEDA = 'jeda';

    public const string APPROVAL_PENDING = 'pending';

    public const string APPROVAL_APPROVED = 'approved';

    public const string APPROVAL_REJECTED = 'rejected';

    /**
     * Supported regional service areas for customer filtering and branch clarity.
     *
     * @var list<string>
     */
    public const array AREAS = [
        'Denpasar',
        'Jimbaran',
        'Canggu',
        'Seminyak',
        'Ubud',
        'Sanur',
        'Kuta',
        'Nusa Dua',
        'Tabanan',
        'Gianyar',
    ];

    /**
     * Default model attributes for newly instantiated records.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'approval_status' => self::APPROVAL_APPROVED,
        'use_stations' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'category_id',
        'name',
        'slug',
        'logo_path',
        'description',
        'address',
        'area',
        'phone',
        'timezone',
        'status',
        'use_stations',
        'is_active',
        'is_suspended',
        'approval_status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'is_display_enabled',
        'display_token_hash',
        'display_token_created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_stations' => 'boolean',
            'is_active' => 'boolean',
            'is_suspended' => 'boolean',
            'is_display_enabled' => 'boolean',
            'display_token_created_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     * Ensures that when a business is created with a user_id, a matching owner membership is created.
     */
    protected static function booted(): void
    {
        static::created(function (Business $business) {
            if (! $business->user_id) {
                return;
            }

            $connection = $business->getConnectionName();

            try {
                $organization = null;

                if (Schema::connection($connection)->hasTable('business_organizations')) {
                    if ($business->organization_id) {
                        $organization = BusinessOrganization::on($connection)->find($business->organization_id);
                    } else {
                        $organization = BusinessOrganization::on($connection)->create([
                            'owner_user_id' => $business->user_id,
                            'name' => $business->name,
                            'slug' => static::generateUniqueOrganizationSlug($business, $connection),
                            'description' => $business->description,
                            'timezone' => $business->timezone ?: 'Asia/Makassar',
                            'is_active' => true,
                        ]);

                        $business->forceFill(['organization_id' => $organization->id])->saveQuietly();
                    }

                    if ($organization && Schema::connection($connection)->hasTable('organization_memberships')) {
                        OrganizationMembership::on($connection)->firstOrCreate(
                            [
                                'organization_id' => $organization->id,
                                'user_id' => $business->user_id,
                            ],
                            [
                                'role' => OrganizationMembership::ROLE_OWNER,
                                'is_active' => true,
                                'joined_at' => $business->created_at ?? now(),
                            ]
                        );
                    }
                }

                $membership = null;

                if (Schema::connection($connection)->hasTable('business_memberships')) {
                    // Ownership is scoped per branch: the same user may own/manage several branches.
                    $membership = BusinessMembership::on($connection)->firstOrCreate(
                        [
                            'business_id' => $business->id,
                            'user_id' => $business->user_id,
                        ],
                        [
                            'organization_id' => $business->organization_id,
                            'role' => BusinessMembership::ROLE_OWNER,
                            'is_active' => true,
                            'joined_at' => $business->created_at ?? now(),
                        ]
                    );
                }

                if (Schema::connection($connection)->hasTable('business_counters')) {
                    $counter = BusinessCounter::on($connection)->firstOrCreate(
                        ['business_id' => $business->id, 'code' => 'L-01'],
                        [
                            'name' => 'Loket 1',
                            'description' => 'Loket pelayanan utama',
                            'is_active' => true,
                        ]
                    );

                    if ($membership && Schema::connection($connection)->hasTable('counter_operator_assignments')) {
                        $counter->assignedMemberships()->syncWithoutDetaching([$membership->id]);
                    }
                }
            } catch (\Throwable) {
                // Suppress if table does not exist on target connection during isolated DB tests
            }
        });

        static::updated(function (Business $business) {
            if ($business->wasChanged('user_id') && $business->user_id) {
                $connection = $business->getConnectionName();
                try {
                    if (Schema::connection($connection)->hasTable('business_memberships')) {
                        BusinessMembership::on($connection)->updateOrCreate(
                            [
                                'business_id' => $business->id,
                                'user_id' => $business->user_id,
                            ],
                            [
                                'organization_id' => $business->organization_id,
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
     * Generate an organization slug without mutating the branch public slug.
     */
    protected static function generateUniqueOrganizationSlug(Business $business, ?string $connection): string
    {
        $baseSlug = Str::slug($business->slug ?: $business->name);
        if ($baseSlug === '') {
            $baseSlug = 'organisasi-'.Str::lower(Str::random(6));
        }

        $slug = $baseSlug;
        $counter = 1;

        while (BusinessOrganization::on($connection)->where('slug', $slug)->exists()) {
            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }

        return $slug;
    }

    /**
     * Get the public URL for the business logo.
     */
    public function getLogoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Determine whether the business has been approved by platform administrator.
     */
    public function isApproved(): bool
    {
        return ($this->approval_status ?? self::APPROVAL_APPROVED) === self::APPROVAL_APPROVED;
    }

    /**
     * Determine whether the business is waiting for administrator review.
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Determine whether the business registration was rejected by administrator.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /**
     * Determine whether the business is currently open for queue intake.
     */
    public function isOpen(): bool
    {
        return $this->is_active !== false
            && $this->status === self::STATUS_BUKA
            && ! $this->is_suspended
            && $this->isApproved();
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
        return $this->is_active === false || $this->status === self::STATUS_TUTUP || $this->is_suspended || ! $this->isApproved();
    }

    /**
     * Scope to stores customers may browse: approved, not suspended, and active.
     *
     * @param  Builder<Business>  $query
     */
    public function scopeVisibleToCustomers(Builder $query): Builder
    {
        return $query->where('is_suspended', false)
            ->where('approval_status', self::APPROVAL_APPROVED)
            ->where(fn (Builder $inner) => $inner->whereNull('is_active')->orWhere('is_active', true));
    }

    /**
     * Today's opening hours in the business timezone, formatted like "08.00–21.00" (or "Tutup").
     */
    public function todayScheduleLabel(): ?string
    {
        $hours = $this->todayHours();

        if (! $hours) {
            return null;
        }

        return str_replace([':', ' - '], ['.', '–'], $hours->getFormattedSchedule());
    }

    /**
     * Today's opening hours row in the business timezone, if the merchant configured one.
     */
    public function todayHours(): ?BusinessHour
    {
        $today = (int) now($this->timezone ?: config('app.timezone'))->dayOfWeek;

        return $this->hours->firstWhere('day_of_week', $today);
    }

    /**
     * Prominent opening-hours badge for customers, e.g. "Buka s/d 21.00" or "Buka pukul 08.00".
     *
     * @return array{label: string, tone: 'open'|'paused'|'closed'}
     */
    public function customerHoursBadge(): array
    {
        $hours = $this->todayHours();
        $closeTime = $hours && ! $hours->is_closed && $hours->close_time ? str_replace(':', '.', substr((string) $hours->close_time, 0, 5)) : null;
        $openTime = $hours && ! $hours->is_closed && $hours->open_time ? str_replace(':', '.', substr((string) $hours->open_time, 0, 5)) : null;

        if ($this->isOpen()) {
            return ['label' => $closeTime ? 'Buka s/d '.$closeTime : 'Buka sekarang', 'tone' => 'open'];
        }

        if ($this->isPaused() && ! $this->is_suspended) {
            return ['label' => 'Antrean dijeda', 'tone' => 'paused'];
        }

        if ($hours?->is_closed) {
            return ['label' => 'Libur hari ini', 'tone' => 'closed'];
        }

        $isBeforeOpening = $openTime && now($this->timezone ?: config('app.timezone'))->format('H.i') < $openTime;

        return ['label' => $isBeforeOpening ? 'Buka pukul '.$openTime : 'Tutup', 'tone' => 'closed'];
    }

    /**
     * Stock photo shown behind the store card when the merchant has not uploaded a cover.
     */
    public function coverImagePath(): ?string
    {
        $name = Str::lower($this->name);

        return match (true) {
            Str::contains($name, ['pastry', 'roti', 'bakery', 'cake', 'kue']) => 'images/stores/pastry.jpg',
            Str::contains($name, ['teh', 'tea', 'jus', 'boba']) => 'images/stores/kuliner.jpg',
            Str::contains($name, ['kopi', 'coffee', 'kafe', 'cafe']) => 'images/stores/kopi.jpg',
            Str::contains($name, ['dapur', 'warung', 'resto', 'rumah makan', 'makan']) => 'images/stores/restoran.jpg',
            default => $this->category?->coverImagePath(),
        };
    }

    /**
     * Rough walk-up wait for customers browsing stores: people waiting times the average service
     * duration, rounded up to 5 minutes. Null when nobody waits or no duration is configured.
     */
    public function customerWaitMinutes(?int $waitingCount = null): ?int
    {
        $waiting = $waitingCount ?? (int) ($this->getAttributes()['waiting_count'] ?? 0);

        if ($waiting < 1) {
            return null;
        }

        $averageMinutes = array_key_exists('avg_service_minutes', $this->getAttributes())
            ? $this->getAttributes()['avg_service_minutes']
            : $this->services()->active()->avg('duration_minutes');

        if (! $averageMinutes) {
            return null;
        }

        return (int) (ceil($waiting * (float) $averageMinutes / 5) * 5);
    }

    /**
     * Range-based wait time estimate scaled by the active queue depth and counter count in this store.
     *
     * @return array{min_minutes: int, max_minutes: int, label: string}|null
     */
    public function customerWaitRange(?int $waitingCount = null): ?array
    {
        $waiting = $waitingCount ?? (int) ($this->getAttributes()['waiting_count'] ?? 0);

        $avgMinutes = array_key_exists('avg_service_minutes', $this->getAttributes())
            ? $this->getAttributes()['avg_service_minutes']
            : $this->services()->active()->avg('duration_minutes');

        if (! $avgMinutes) {
            return null;
        }

        if ($waiting < 1) {
            return [
                'min_minutes' => 0,
                'max_minutes' => 5,
                'label' => '0–5 mnt',
            ];
        }

        $minService = array_key_exists('min_service_minutes', $this->getAttributes())
            ? ($this->getAttributes()['min_service_minutes'] ?: $avgMinutes)
            : ($this->services()->active()->min('duration_minutes') ?: $avgMinutes);

        $maxService = array_key_exists('max_service_minutes', $this->getAttributes())
            ? ($this->getAttributes()['max_service_minutes'] ?: $avgMinutes)
            : ($this->services()->active()->max('duration_minutes') ?: $avgMinutes);

        $activeCounters = $this->effectiveStationCount();

        $lowService = min((float) $minService, (float) $avgMinutes * 0.85);
        $highService = max((float) $maxService, (float) $avgMinutes * 1.25);

        $rawMin = ($waiting * $lowService) / $activeCounters;
        $rawMax = ($waiting * $highService) / $activeCounters;

        $minMinutes = $rawMin < 10
            ? max(1, (int) round($rawMin))
            : (int) (floor($rawMin / 5) * 5);

        $maxMinutes = $rawMax < 10
            ? max($minMinutes + 3, (int) ceil($rawMax))
            : max($minMinutes + 5, (int) (ceil($rawMax / 5) * 5));

        return [
            'min_minutes' => $minMinutes,
            'max_minutes' => $maxMinutes,
            'label' => sprintf('%d–%d mnt', $minMinutes, $maxMinutes),
        ];
    }

    /**
     * Average rating from loaded `reviews_avg_rating` (via withAvg) or a fresh query, rounded to one decimal.
     */
    public function customerRating(): ?float
    {
        $average = array_key_exists('reviews_avg_rating', $this->attributes)
            ? $this->attributes['reviews_avg_rating']
            : $this->customerReviews()->avg('rating');

        return $average !== null ? round((float) $average, 1) : null;
    }

    /**
     * Number of customer reviews from loaded `reviews_count` (via withCount) or a fresh query.
     */
    public function customerReviewCount(): int
    {
        return (int) (array_key_exists('reviews_count', $this->attributes)
            ? $this->attributes['reviews_count']
            : $this->customerReviews()->count());
    }

    /**
     * District/area label for store cards and filters.
     */
    public function areaLabel(): ?string
    {
        if (filled($this->area)) {
            return trim((string) $this->area);
        }

        return $this->cityLabel();
    }

    /**
     * City shown on customer cards: explicit area or the last segment of the street address.
     */
    public function cityLabel(): ?string
    {
        if (filled($this->area)) {
            return trim((string) $this->area);
        }

        $city = trim((string) Str::afterLast((string) $this->address, ','));

        return $city !== '' ? $city : null;
    }

    /**
     * Other approved, active branches under the same organization.
     *
     * @return Collection<int, Business>
     */
    public function siblingBranches()
    {
        if (! $this->organization_id) {
            return new Collection;
        }

        return static::query()
            ->visibleToCustomers()
            ->where('organization_id', $this->organization_id)
            ->where('id', '!=', $this->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Determine whether this branch is activated by its organization.
     */
    public function isActive(): bool
    {
        return $this->is_active !== false;
    }

    /**
     * Get the organization (brand / business group) that owns this branch.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(BusinessOrganization::class, 'organization_id');
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
     * Get the owner of this business (alias for user).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin reviewer who approved or rejected this business.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
     * Determine whether this business operates in multi-station / multi-counter mode.
     */
    public function usesStations(): bool
    {
        return (bool) ($this->use_stations ?? true);
    }

    /**
     * Effective parallel service capacity: 1 in single-station mode, or active counter count (min 1).
     */
    public function effectiveStationCount(): int
    {
        if (! $this->usesStations()) {
            return 1;
        }

        $count = array_key_exists('active_counters_count', $this->getAttributes())
            ? (int) $this->getAttributes()['active_counters_count']
            : $this->activeCounters()->count();

        return max(1, $count);
    }

    /**
     * Determine whether a user is an active owner of this business.
     */
    public function isOwner(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->memberships()
            ->where('user_id', $user->id)
            ->where('role', BusinessMembership::ROLE_OWNER)
            ->where('is_active', true)
            ->exists()) {
            return true;
        }

        return (bool) $this->organization?->canManageBranches($user);
    }

    /**
     * Determine whether a user is an active member (owner or operator) of this business.
     */
    public function isMember(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->memberships()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists()) {
            return true;
        }

        return (bool) $this->organization?->canManageBranches($user);
    }

    /**
     * Determine whether the public display is active and accessible.
     */
    public function isDisplayActive(): bool
    {
        return (bool) $this->is_display_enabled && ! $this->is_suspended && ! empty($this->display_token_hash);
    }

    /**
     * Generate a fresh cryptographic display token and update hash.
     * The raw token is returned for one-time disclosure to the merchant and NEVER stored in the database.
     */
    public function generateDisplayToken(): string
    {
        $token = bin2hex(random_bytes(24));
        $this->forceFill([
            'is_display_enabled' => true,
            'display_token_hash' => hash('sha256', $token),
            'display_token_created_at' => now(),
        ])->save();

        return $token;
    }

    /**
     * Revoke display access.
     */
    public function revokeDisplayAccess(): void
    {
        $this->forceFill([
            'is_display_enabled' => false,
            'display_token_hash' => null,
            'display_token_created_at' => null,
        ])->save();
    }

    /**
     * Get the public display URL given a raw token.
     */
    public function getDisplayUrl(?string $token = null): ?string
    {
        if (! $token) {
            return null;
        }

        return route('display.show', ['token' => $token]);
    }

    /**
     * Get customer reviews for this business.
     */
    public function customerReviews(): HasMany
    {
        return $this->hasMany(CustomerReview::class);
    }

    /**
     * Get the average customer review rating.
     */
    public function averageRating(): float
    {
        return round((float) ($this->customerReviews()->avg('rating') ?? 0), 1);
    }

    /**
     * Get the total number of customer reviews.
     */
    public function totalReviewsCount(): int
    {
        return $this->customerReviews()->count();
    }
}
