<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QueueTicket extends Model
{
    use HasFactory;

    public const string STATUS_MENUNGGU = 'menunggu';

    public const string STATUS_DIPANGGIL = 'dipanggil';

    public const string STATUS_SEDANG_DILAYANI = 'sedang_dilayani';

    public const string STATUS_SELESAI = 'selesai';

    public const string STATUS_DILEWATI = 'dilewati';

    public const string STATUS_TIDAK_HADIR = 'tidak_hadir';

    public const string STATUS_DIBATALKAN = 'dibatalkan';

    // English constant aliases
    public const string STATUS_WAITING = self::STATUS_MENUNGGU;

    public const string STATUS_CALLED = self::STATUS_DIPANGGIL;

    public const string STATUS_SERVING = self::STATUS_SEDANG_DILAYANI;

    public const string STATUS_COMPLETED = self::STATUS_SELESAI;

    public const string STATUS_SKIPPED = self::STATUS_DILEWATI;

    public const string STATUS_NO_SHOW = self::STATUS_TIDAK_HADIR;

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
        'counter_id',
        'user_id',
        'ticket_number',
        'sequence_number',
        'booking_slot_start',
        'booking_slot_end',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_email_consented_at',
        'source',
        'status',
        'last_notified_status',
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
            'booking_slot_start' => 'datetime',
            'booking_slot_end' => 'datetime',
            'customer_email_consented_at' => 'datetime',
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
     * @param  Builder<QueueTicket>  $query
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
     * @param  Builder<QueueTicket>  $query
     */
    public function scopeTerminal(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_SELESAI,
            self::STATUS_TIDAK_HADIR,
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
            self::STATUS_TIDAK_HADIR,
            self::STATUS_DILEWATI,
            self::STATUS_DIBATALKAN,
        ], true);
    }

    /**
     * Single source of truth for how each ticket status is presented in the UI (Bahasa Indonesia).
     *
     * Tones map to badge colours: warning (amber), info (blue), brand (brand blue),
     * success (green), neutral (grey), caution (orange), danger (red).
     *
     * @return array<string, array{label: string, tone: string}>
     */
    public static function statusPresentation(): array
    {
        return [
            self::STATUS_MENUNGGU => ['label' => 'Menunggu', 'tone' => 'warning'],
            self::STATUS_DIPANGGIL => ['label' => 'Dipanggil', 'tone' => 'info'],
            self::STATUS_SEDANG_DILAYANI => ['label' => 'Sedang dilayani', 'tone' => 'brand'],
            self::STATUS_SELESAI => ['label' => 'Selesai', 'tone' => 'success'],
            self::STATUS_DILEWATI => ['label' => 'Dilewati', 'tone' => 'neutral'],
            self::STATUS_TIDAK_HADIR => ['label' => 'Tidak hadir', 'tone' => 'caution'],
            self::STATUS_DIBATALKAN => ['label' => 'Dibatalkan', 'tone' => 'danger'],
        ];
    }

    /**
     * Normalise a status value, accepting the English aliases (waiting, called, serving, ...).
     */
    public static function normalizeStatus(?string $status): ?string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'waiting' => self::STATUS_MENUNGGU,
            'called' => self::STATUS_DIPANGGIL,
            'serving' => self::STATUS_SEDANG_DILAYANI,
            'completed' => self::STATUS_SELESAI,
            'skipped' => self::STATUS_DILEWATI,
            'no_show' => self::STATUS_TIDAK_HADIR,
            'cancelled' => self::STATUS_DIBATALKAN,
            default => array_key_exists($status, self::statusPresentation()) ? $status : null,
        };
    }

    /**
     * Whether the given value is a known ticket status (including English aliases).
     */
    public static function isTicketStatus(?string $status): bool
    {
        return self::normalizeStatus($status) !== null;
    }

    /**
     * Human label for a ticket status, e.g. "Sedang dilayani".
     */
    public static function labelForStatus(?string $status): string
    {
        $normalized = self::normalizeStatus($status);

        return $normalized !== null
            ? self::statusPresentation()[$normalized]['label']
            : ucfirst(str_replace('_', ' ', (string) $status));
    }

    /**
     * Presentation tone for a ticket status (see statusPresentation()).
     */
    public static function toneForStatus(?string $status): string
    {
        $normalized = self::normalizeStatus($status);

        return $normalized !== null ? self::statusPresentation()[$normalized]['tone'] : 'neutral';
    }

    /**
     * Human label for this ticket's status.
     */
    public function statusLabel(): string
    {
        return self::labelForStatus($this->status);
    }

    /**
     * Presentation tone for this ticket's status.
     */
    public function statusTone(): string
    {
        return self::toneForStatus($this->status);
    }

    /**
     * Formatted start time of the customer's booking slot in the business timezone, e.g. "11:00".
     */
    public function bookingSlotLabel(?string $timezone = null): ?string
    {
        if (! $this->booking_slot_start) {
            return null;
        }

        $tz = $timezone ?: ($this->business?->timezone ?: config('app.timezone', 'Asia/Makassar'));

        return $this->booking_slot_start->copy()->setTimezone($tz)->format('H:i');
    }

    /**
     * Formatted window label for the customer's booking slot, e.g. "11:00 - 12:00".
     */
    public function bookingSlotWindowLabel(?string $timezone = null): ?string
    {
        if (! $this->booking_slot_start) {
            return null;
        }

        $tz = $timezone ?: ($this->business?->timezone ?: config('app.timezone', 'Asia/Makassar'));
        $start = $this->booking_slot_start->copy()->setTimezone($tz)->format('H:i');
        $end = $this->booking_slot_end
            ? $this->booking_slot_end->copy()->setTimezone($tz)->format('H:i')
            : $this->booking_slot_start->copy()->addHour()->setTimezone($tz)->format('H:i');

        return "{$start} - {$end}";
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
     * Get the counter assigned to serve this ticket.
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(BusinessCounter::class, 'counter_id');
    }

    /**
     * Get the authenticated customer account associated with this ticket, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the audit status histories for this ticket.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(QueueStatusHistory::class);
    }

    /**
     * Get the browser push subscriptions for this ticket.
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Get the customer review for this ticket.
     */
    public function review(): HasOne
    {
        return $this->hasOne(CustomerReview::class);
    }

    /**
     * Determine whether this ticket has already been reviewed by the customer.
     */
    public function hasReview(): bool
    {
        return $this->review()->exists();
    }
}
