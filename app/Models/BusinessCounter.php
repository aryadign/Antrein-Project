<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BusinessCounter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'code',
        'description',
        'is_active',
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
        ];
    }

    /**
     * Scope query to only include active counters.
     *
     * @param  Builder<BusinessCounter>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the business that owns this counter.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the business memberships assigned to operate this counter.
     */
    public function assignedMemberships(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessMembership::class,
            'counter_operator_assignments',
            'business_counter_id',
            'business_membership_id'
        )->withTimestamps();
    }

    /**
     * Get all queue tickets served or called at this counter.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'counter_id');
    }

    /**
     * Get the current active ticket at this counter (dipanggil or sedang_dilayani).
     */
    public function activeTicket(): HasOne
    {
        return $this->hasOne(QueueTicket::class, 'counter_id')
            ->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])
            ->latest('called_at');
    }

    /**
     * Check if this counter currently has an active ticket.
     */
    public function hasActiveTicket(): bool
    {
        return $this->tickets()
            ->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])
            ->exists();
    }

    /**
     * Check if a given user is authorized to operate this counter.
     * - Business Owner: Can operate all active counters in their business.
     * - Operator: Can only operate counters assigned to their membership.
     */
    public function canBeOperatedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        $business = $user->business;
        if (! $business || $business->id !== $this->business_id) {
            return false;
        }

        // Owner can operate any active counter
        if ($user->isOwner()) {
            return true;
        }

        // Operator must have active membership and assignment
        $membership = $user->businessMembership;
        if (! $membership || ! $membership->is_active) {
            return false;
        }

        return $this->assignedMemberships()
            ->where('business_memberships.id', $membership->id)
            ->exists();
    }
}
