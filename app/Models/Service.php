<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'daily_quota',
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
            'duration_minutes' => 'integer',
            'price' => 'decimal:2',
            'daily_quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope query to only include active services.
     *
     * @param Builder<Service> $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate remaining quota for a given daily queue session based on approved rule:
     * Tickets consuming quota: menunggu, dipanggil, sedang_dilayani, selesai, dilewati.
     * Only dibatalkan releases quota.
     */
    public function getRemainingQuotaForDailyQueue(DailyQueue $dailyQueue): ?int
    {
        if ($this->daily_quota === null) {
            return null; // Unlimited quota
        }

        $consumedCount = $this->queueTickets()
            ->where('daily_queue_id', $dailyQueue->id)
            ->where('status', '!=', QueueTicket::STATUS_DIBATALKAN)
            ->count();

        return max(0, $this->daily_quota - $consumedCount);
    }

    /**
     * Get the business that offers this service.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get queue tickets associated with this service.
     */
    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}

