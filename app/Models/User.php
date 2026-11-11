<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const string ROLE_ADMIN = 'admin';
    public const string ROLE_MERCHANT = 'merchant';

    /**
     * Cached active branch resolution for the current request.
     */
    protected bool $activeBusinessResolved = false;

    protected ?Business $activeBusinessCache = null;

    /**
     * Cached active branch membership resolution for the current request.
     */
    protected bool $activeMembershipResolved = false;

    protected ?BusinessMembership $activeMembershipCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine whether the user has administrator privileges.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Determine whether the user is a registered merchant (Owner or Operator).
     */
    public function isMerchant(): bool
    {
        return $this->role === self::ROLE_MERCHANT;
    }

    /**
     * Determine whether the user is an owner/manager of the active branch.
     *
     * Organization owners and administrators are treated as branch owners because
     * they have organization-level authority over every branch in the organization.
     */
    public function isOwner(): bool
    {
        return $this->businessRole() === BusinessMembership::ROLE_OWNER;
    }

    /**
     * Determine whether the user is an operator staff member of the active branch.
     */
    public function isOperator(): bool
    {
        return $this->businessRole() === BusinessMembership::ROLE_OPERATOR;
    }

    /**
     * Get the effective role ('owner' or 'operator') of this user in the active branch.
     */
    public function businessRole(): ?string
    {
        $business = $this->business;

        return $business ? $this->roleInBusiness($business) : null;
    }

    /**
     * Resolve the effective branch-scoped role for a specific branch.
     */
    public function roleInBusiness(Business $business): ?string
    {
        if ($business->user_id === $this->id) {
            return BusinessMembership::ROLE_OWNER;
        }

        $membership = $this->businessMemberships()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($membership) {
            return $membership->role;
        }

        $organization = $business->organization;

        if ($organization && $organization->canManageBranches($this)) {
            return BusinessMembership::ROLE_OWNER;
        }

        return null;
    }

    /**
     * Determine whether this user may access the given branch (explicitly or through organization scope).
     */
    public function canAccessBusiness(Business $business): bool
    {
        return $this->roleInBusiness($business) !== null;
    }

    /**
     * Determine whether the user's active branch membership is currently active.
     */
    public function isBusinessMemberActive(): bool
    {
        $business = $this->business;

        return $business !== null && $this->roleInBusiness($business) !== null;
    }

    /**
     * Determine whether the merchant has completed onboarding for the active branch.
     */
    public function hasCompletedOnboarding(): bool
    {
        if (! $this->isMerchant()) {
            return true;
        }

        // Operators join an already-onboarded branch, so onboarding is considered complete
        if ($this->isOperator() || $this->businessMemberships()->where('role', BusinessMembership::ROLE_OPERATOR)->exists()) {
            return true;
        }

        $business = $this->business;
        if (! $business) {
            return false;
        }

        return $business->services()->exists();
    }

    /**
     * Get the counters that this user can operate on the active branch.
     *
     * @return Collection<int, BusinessCounter>
     */
    public function assignedCounters(): Collection
    {
        $business = $this->business;

        if (! $business) {
            return collect();
        }

        if ($this->isOwner()) {
            return $business->activeCounters()->get();
        }

        if ($this->isOperator()) {
            $membership = $this->businessMembership;

            if ($membership && $membership->business_id === $business->id) {
                return $membership->assignedCounters()
                    ->where('is_active', true)
                    ->get();
            }
        }

        return collect();
    }

    /**
     * Determine whether this user is authorized to operate the given counter.
     */
    public function canOperateCounter(BusinessCounter $counter): bool
    {
        return $counter->canBeOperatedBy($this);
    }

    /**
     * Get the merchant business associated with this account (Owner or Operator).
     */
    public function business(): HasOneThrough|HasOne
    {
        return $this->hasOneThrough(
            Business::class,
            BusinessMembership::class,
            'user_id',       // Foreign key on BusinessMembership table
            'id',            // Foreign key on Business table
            'id',            // Local key on User table
            'business_id'    // Local key on BusinessMembership table
        )->orderBy('business_memberships.id');
    }

    /**
     * Get all branches (business units) explicitly joined by this user.
     */
    public function businessMemberships(): HasMany
    {
        return $this->hasMany(BusinessMembership::class, 'user_id');
    }

    /**
     * Get all branches accessible to this user.
     *
     * Access is granted by an active branch membership, direct legacy ownership,
     * or an active owner/admin role in the organization that owns the branch.
     */
    public function accessibleBusinessesQuery(): Builder
    {
        return Business::query()
            ->where(function (Builder $query) {
                $query->where('user_id', $this->id)
                    ->orWhereHas('memberships', function (Builder $membershipQuery) {
                        $membershipQuery->where('user_id', $this->id)
                            ->where('is_active', true);
                    })
                    ->orWhereHas('organization.memberships', function (Builder $organizationQuery) {
                        $organizationQuery->where('user_id', $this->id)
                            ->where('is_active', true)
                            ->whereIn('role', [
                                OrganizationMembership::ROLE_OWNER,
                                OrganizationMembership::ROLE_ADMIN,
                            ]);
                    });
            })
            ->orderByDesc('is_active')
            ->orderBy('id');
    }

    /**
     * Get all accessible branches as a collection (eager-load friendly, deterministic order).
     *
     * @return Collection<int, Business>
     */
    public function accessibleBusinesses(): Collection
    {
        return $this->accessibleBusinessesQuery()->get();
    }

    /**
     * Resolve the active branch for the current request/session.
     */
    public function activeBusiness(): ?Business
    {
        if ($this->activeBusinessResolved) {
            return $this->activeBusinessCache;
        }

        $candidateId = null;

        if (app()->bound('request') && request()->hasSession()) {
            $candidateId = request()->session()->get('active_business_id');
        }

        $business = null;

        if ($candidateId) {
            $business = $this->accessibleBusinessesQuery()
                ->whereKey((int) $candidateId)
                ->first();
        }

        if (! $business) {
            $business = $this->accessibleBusinessesQuery()->first();
        }

        $this->activeBusinessCache = $business;
        $this->activeBusinessResolved = true;

        return $business;
    }

    /**
     * Forget the cached active branch (used after switching branches).
     */
    public function forgetActiveBusiness(): void
    {
        $this->activeBusinessResolved = false;
        $this->activeBusinessCache = null;
        $this->activeMembershipResolved = false;
        $this->activeMembershipCache = null;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetRelation($relation)
    {
        if ($relation === 'business' || $relation === 'businessMembership') {
            $this->forgetActiveBusiness();
        }

        return parent::unsetRelation($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function unsetRelations()
    {
        $this->forgetActiveBusiness();

        return parent::unsetRelations();
    }

    /**
     * Accessor for $user->business — resolves the active branch.
     */
    public function getBusinessAttribute(): ?Business
    {
        return $this->activeBusiness();
    }

    /**
     * Get the directly owned business (where businesses.user_id = users.id).
     *
     * Retained for backward compatibility with legacy single-business records.
     */
    public function ownedBusiness(): HasOne
    {
        return $this->hasOne(Business::class, 'user_id')->orderBy('id');
    }

    /**
     * Get all branches directly owned by this user (legacy primary ownership).
     */
    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'user_id');
    }

    /**
     * Get the business membership record for the active branch.
     */
    public function getBusinessMembershipAttribute(): ?BusinessMembership
    {
        if ($this->activeMembershipResolved) {
            return $this->activeMembershipCache;
        }

        $business = $this->business;
        $membership = null;

        if ($business) {
            $membership = $this->businessMemberships()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->first();
        }

        $membership ??= $this->businessMemberships()
            ->where('is_active', true)
            ->orderBy('business_id')
            ->first();

        $this->activeMembershipCache = $membership;
        $this->activeMembershipResolved = true;

        return $membership;
    }

    /**
     * Get the business membership relation (per-branch memberships).
     */
    public function businessMembershipRecord(): HasOne
    {
        return $this->hasOne(BusinessMembership::class, 'user_id')->orderBy('id');
    }

    /**
     * Get all organization memberships held by this user.
     */
    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class, 'user_id');
    }

    /**
     * Get all organizations this user is a member of.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(BusinessOrganization::class, 'organization_memberships')
            ->withPivot(['role', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get organizations directly owned by this user.
     */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(BusinessOrganization::class, 'owner_user_id');
    }

    /**
     * Resolve the organization this user primarily manages/belongs to.
     *
     * AntreIn intentionally keeps a user within one organization (multi-branch, not multi-group)
     * to preserve tenant isolation.
     */
    public function primaryOrganization(): ?BusinessOrganization
    {
        $organizationId = $this->organizationMemberships()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('organization_id');

        if ($organizationId) {
            return BusinessOrganization::find($organizationId);
        }

        return $this->ownedOrganizations()->orderBy('id')->first();
    }

    /**
     * Resolve the organization this user may manage (owner/admin).
     */
    public function managedOrganization(): ?BusinessOrganization
    {
        $organizationId = $this->organizationMemberships()
            ->where('is_active', true)
            ->whereIn('role', [OrganizationMembership::ROLE_OWNER, OrganizationMembership::ROLE_ADMIN])
            ->orderBy('id')
            ->value('organization_id');

        if ($organizationId) {
            return BusinessOrganization::find($organizationId);
        }

        return $this->ownedOrganizations()->orderBy('id')->first();
    }

    /**
     * Determine whether this user can manage organization-level settings.
     */
    public function canManageOrganization(): bool
    {
        return $this->managedOrganization() !== null;
    }

    /**
     * Resolve the active organization role for this user.
     */
    public function organizationRole(): ?string
    {
        return $this->primaryOrganization()?->roleOf($this);
    }

    /**
     * Get the activity logs triggered by this user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the notification preferences for this user.
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    /**
     * Get the browser push subscriptions registered for this user.
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Determine whether the user wants to receive email notifications for new online tickets.
     */
    public function wantsEmailOnNewTicket(): bool
    {
        if (! $this->hasVerifiedEmail()) {
            return false;
        }

        $pref = $this->notificationPreference;
        return $pref ? (bool) $pref->email_on_new_online_ticket : true;
    }

    /**
     * Determine whether the user wants to receive browser push notifications for new online tickets.
     */
    public function wantsBrowserPushOnNewTicket(): bool
    {
        $pref = $this->notificationPreference;
        return $pref ? (bool) $pref->browser_on_new_online_ticket : true;
    }
}
