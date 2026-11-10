<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
     * Determine whether the user is an owner of their associated business.
     */
    public function isOwner(): bool
    {
        return $this->businessRole() === BusinessMembership::ROLE_OWNER;
    }

    /**
     * Determine whether the user is an operator staff member of their associated business.
     */
    public function isOperator(): bool
    {
        return $this->businessRole() === BusinessMembership::ROLE_OPERATOR;
    }

    /**
     * Get the business membership role ('owner' or 'operator') of this user.
     */
    public function businessRole(): ?string
    {
        $membership = $this->businessMembership;
        if ($membership) {
            return $membership->role;
        }

        // Fallback for direct owner (M3 compatibility)
        if ($this->ownedBusiness()->exists()) {
            return BusinessMembership::ROLE_OWNER;
        }

        return null;
    }

    /**
     * Determine whether the user's business membership is currently active.
     */
    public function isBusinessMemberActive(): bool
    {
        $membership = $this->businessMembership;
        if ($membership) {
            return $membership->is_active;
        }

        return $this->isMerchant() && $this->ownedBusiness()->exists();
    }

    /**
     * Determine whether the merchant has completed the onboarding flow.
     */
    public function hasCompletedOnboarding(): bool
    {
        if (! $this->isMerchant()) {
            return true;
        }

        // Operators join an already-onboarded business, so onboarding is considered complete
        if ($this->isOperator()) {
            return true;
        }

        $business = $this->business;
        if (! $business) {
            return false;
        }

        return $business->services()->exists();
    }

    /**
     * Get the counters that this user can operate (active counters).
     *
     * @return Collection<int, BusinessCounter>
     */
    public function assignedCounters(): Collection
    {
        if ($this->isOwner()) {
            return $this->business?->activeCounters ?? collect();
        }

        if ($this->isOperator()) {
            return $this->businessMembership?->assignedCounters()
                ->where('is_active', true)
                ->get() ?? collect();
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
        );
    }

    /**
     * Accessor fallback for $user->business to guarantee compatibility if membership is pending.
     */
    public function getBusinessAttribute(): ?Business
    {
        if ($this->relationLoaded('business')) {
            $business = $this->getRelation('business');
            if ($business) {
                return $business;
            }
        }

        // 1. Through membership
        $membership = $this->businessMembership()->with('business')->first();
        if ($membership && $membership->business) {
            $this->setRelation('business', $membership->business);
            return $membership->business;
        }

        // 2. Direct owner fallback (MVP compatibility)
        $owned = $this->ownedBusiness()->first();
        if ($owned) {
            $this->setRelation('business', $owned);
            return $owned;
        }

        return null;
    }

    /**
     * Get the directly owned business (where businesses.user_id = users.id).
     */
    public function ownedBusiness(): HasOne
    {
        return $this->hasOne(Business::class, 'user_id');
    }

    /**
     * Get the business membership record for this user.
     */
    public function businessMembership(): HasOne
    {
        return $this->hasOne(BusinessMembership::class, 'user_id');
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
