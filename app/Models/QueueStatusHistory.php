<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueStatusHistory extends Model
{
    use HasFactory;

    public const string EVENT_STATUS_CHANGED = 'status_changed';
    public const string EVENT_TICKET_RECALLED = 'ticket_recalled';
    public const string EVENT_TICKET_SKIPPED = 'ticket_skipped';
    public const string EVENT_TICKET_CANCELLED = 'ticket_cancelled';
    public const string EVENT_NOTE_ADDED = 'note_added';

    public const string ACTOR_MERCHANT = 'merchant';
    public const string ACTOR_CUSTOMER = 'customer';
    public const string ACTOR_SYSTEM = 'system';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'queue_ticket_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
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
            'created_at' => 'datetime',
            'actor_id' => 'integer',
        ];
    }

    /**
     * Get the queue ticket being audited.
     */
    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }
}

