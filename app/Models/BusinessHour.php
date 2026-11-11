<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessHour extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * Day of week names in Indonesian.
     *
     * @var array<int, string>
     */
    public const array DAY_NAMES = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * Get the Indonesian day name for this schedule record.
     */
    public function getDayName(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Hari '.$this->day_of_week;
    }

    /**
     * Get a human-readable schedule string (e.g., "09:00 - 21:00" or "Tutup").
     */
    public function getFormattedSchedule(): string
    {
        if ($this->is_closed) {
            return 'Tutup';
        }

        $open = $this->open_time ? substr($this->open_time, 0, 5) : '00:00';
        $close = $this->close_time ? substr($this->close_time, 0, 5) : '00:00';

        return "{$open} - {$close}";
    }

    /**
     * Get the business that owns this schedule.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}

