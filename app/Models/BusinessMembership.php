<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusinessMembership extends Model
{
    use HasFactory;

    public const string ROLE_OWNER = 'owner';
    public const string ROLE_OPERATOR = 'operator';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'business_id',
        'user_id',
        'role',
        'is_active',
        'joined_at',
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
            'joined_at' => 'datetime',
        ];
    }

    /**
     * Get the business that owns this membership.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the organization that owns the branch of this membership.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(BusinessOrganization::class, 'organization_id');
    }

    /**
     * Get the user linked to this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the counters assigned to this membership.
     */
    public function assignedCounters(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessCounter::class,
            'counter_operator_assignments',
            'business_membership_id',
            'business_counter_id'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (BusinessMembership $membership) {
            if (! $membership->organization_id && $membership->business_id) {
                $membership->organization_id = Business::whereKey($membership->business_id)->value('organization_id');
            }
        });

        static::created(function (BusinessMembership $membership) {
            if ($membership->role === self::ROLE_OPERATOR && $membership->business) {
                $defaultCounter = $membership->business->counters()->where('is_active', true)->first();
                if ($defaultCounter) {
                    $defaultCounter->assignedMemberships()->syncWithoutDetaching([$membership->id]);
                }
            }
        });
    }

    /**
     * Determine whether the member is an owner of the business.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Determine whether the member is an operator staff of the business.
     */
    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }
}

