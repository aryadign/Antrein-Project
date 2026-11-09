<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueTicket extends Model
{
    use HasFactory;

    public const string STATUS_MENUNGGU = 'menunggu';
    public const string STATUS_DIPANGGIL = 'dipanggil';
    public const string STATUS_SEDANG_DILAYANI = 'sedang_dilayani';
    public const string STATUS_SELESAI = 'selesai';
    public const string STATUS_DILEWATI = 'dilewati';
    public const string STATUS_DIBATALKAN = 'dibatalkan';

    // English constant aliases
    public const string STATUS_WAITING = self::STATUS_MENUNGGU;
    public const string STATUS_CALLED = self::STATUS_DIPANGGIL;
    public const string STATUS_SERVING = self::STATUS_SEDANG_DILAYANI;
    public const string STATUS_COMPLETED = self::STATUS_SELESAI;
    public const string STATUS_SKIPPED = self::STATUS_DILEWATI;
    public const string STATUS_CANCELLED = self::STATUS_DIBATALKAN;

    public const string SOURCE_ONLINE = 'online';
    public const string SOURCE_WALK_IN = 'walk_in';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'daily_queue_id',
        'service_id',
        'ticket_number',
        'sequence_number',
        'customer_name',
        'customer_phone',
        'source',
        'status',
        'access_token',
        'idempotency_key',
        'called_at',
        'recalled_at',
        'service_started_at',
        'completed_at',
        'skipped_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'called_at' => 'datetime',
            'recalled_at' => 'datetime',
            'service_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Scope query to only include active tickets.
     *
     * @param Builder<QueueTicket> $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_MENUNGGU,
            self::STATUS_DIPANGGIL,
            self::STATUS_SEDANG_DILAYANI,
        ]);
    }

    /**
     * Scope query to only include terminal tickets.
     *
     * @param Builder<QueueTicket> $query
     */
    public function scopeTerminal(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_SELESAI,
            self::STATUS_DILEWATI,
            self::STATUS_DIBATALKAN,
        ]);
    }

    /**
     * Determine whether the ticket is actively waiting or being served.
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_MENUNGGU,
            self::STATUS_DIPANGGIL,
            self::STATUS_SEDANG_DILAYANI,
        ], true);
    }

    /**
     * Determine whether the ticket is in a terminal lifecycle state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_SELESAI,
            self::STATUS_DILEWATI,
            self::STATUS_DIBATALKAN,
        ], true);
    }

    /**
     * Get the business that owns this ticket.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the daily queue session this ticket was issued in.
     */
    public function dailyQueue(): BelongsTo
    {
        return $this->belongsTo(DailyQueue::class);
    }

    /**
     * Get the service associated with this ticket.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the audit status histories for this ticket.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(QueueStatusHistory::class);
    }
}

