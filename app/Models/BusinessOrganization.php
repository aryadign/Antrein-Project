<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Business organization (brand / business group) that owns one or more branches.
 *
 * The existing `businesses` table remains the operational branch/tenant boundary.
 * Organization-level data is intentionally limited to identity and ownership so that
 * queue isolation, public slugs, tokens and reporting keep operating per branch.
 */
class BusinessOrganization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'description',
        'timezone',
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
     * Get the primary owner account of this organization.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get all branches (business units) belonging to this organization.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Business::class, 'organization_id');
    }

    /**
     * Get all branches that are not soft-deleted and are activated.
     */
    public function activeBranches(): HasMany
    {
        return $this->branches()->where('is_active', true);
    }

    /**
     * Get all organization-level memberships.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class, 'organization_id');
    }

    /**
     * Get all users that hold an organization-level role.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_memberships')
            ->withPivot(['role', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Resolve the active organization role for a user, if any.
     */
    public function roleOf(User $user): ?string
    {
        if ($this->owner_user_id === $user->id) {
            return OrganizationMembership::ROLE_OWNER;
        }

        $membership = $this->memberships()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $membership?->role;
    }

    /**
     * Determine whether the user is the primary organization owner.
     */
    public function isOwner(User $user): bool
    {
        return $this->roleOf($user) === OrganizationMembership::ROLE_OWNER;
    }

    /**
     * Determine whether the user may manage branches and brand settings.
     */
    public function canManageBranches(User $user): bool
    {
        return in_array($this->roleOf($user), [
            OrganizationMembership::ROLE_OWNER,
            OrganizationMembership::ROLE_ADMIN,
        ], true);
    }

    /**
     * Determine whether the user is an active member of this organization.
     */
    public function isMember(User $user): bool
    {
        return $this->roleOf($user) !== null;
    }
}
