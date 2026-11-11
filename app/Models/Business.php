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
        'organization_id',
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
        'is_active',
        'is_suspended',
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
            'is_active' => 'boolean',
            'is_suspended' => 'boolean',
            'is_display_enabled' => 'boolean',
            'display_token_created_at' => 'datetime',
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

                if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('business_organizations')) {
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

                    if ($organization && \Illuminate\Support\Facades\Schema::connection($connection)->hasTable('organization_memberships')) {
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

                if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('business_memberships')) {
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
        });

        static::updated(function (Business $business) {
            if ($business->wasChanged('user_id') && $business->user_id) {
                $connection = $business->getConnectionName();
                try {
                    if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('business_memberships')) {
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
        $baseSlug = \Illuminate\Support\Str::slug($business->slug ?: $business->name);
        if ($baseSlug === '') {
            $baseSlug = 'organisasi-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
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

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Determine whether the business is currently open for queue intake.
     */
    public function isOpen(): bool
    {
        return $this->is_active !== false && $this->status === self::STATUS_BUKA && ! $this->is_suspended;
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
        return $this->is_active === false || $this->status === self::STATUS_TUTUP || $this->is_suspended;
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

