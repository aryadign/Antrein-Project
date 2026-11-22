<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'image_path',
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
     * Public URL of the photo customers see for this service, if one was uploaded or seeded.
     */
    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://', 'images/'])) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Illustration icon guessed from the service name and description, used when no photo is uploaded.
     */
    public function iconPath(): ?string
    {
        $text = Str::lower($this->name.' '.$this->description);

        return match (true) {
            Str::contains($text, ['kopi', 'coffee', 'espresso', 'americano', 'latte']) => 'images/figma/coffee.svg',
            Str::contains($text, ['teh', 'minum', 'jus', 'matcha', 'boba', 'milk tea', 'es ']) => 'images/figma/cup-soda.svg',
            Str::contains($text, ['makan', 'nasi', 'roti', 'pastry', 'croissant', 'ayam', 'toast', 'mie', 'paket']) => 'images/figma/utensils.svg',
            Str::contains($text, ['rambut', 'cukur', 'jenggot', 'kumis', 'potong', 'shaving']) => 'images/icons/scissors.svg',
            Str::contains($text, ['facial', 'salon', 'creambath', 'kecantikan', 'spa', 'nail', 'makeup']) => 'images/icons/sparkles.svg',
            Str::contains($text, ['periksa', 'dokter', 'gigi', 'mata', 'konsultasi', 'kesehatan']) => 'images/icons/stethoscope.svg',
            Str::contains($text, ['servis', 'service', 'perbaik', 'reparasi', 'instal']) => 'images/icons/wrench.svg',
            default => null,
        };
    }

    /**
     * Stock photo matching the service name, then the store category, used when no photo is uploaded.
     */
    public function fallbackImagePath(?BusinessCategory $category = null): ?string
    {
        $text = Str::lower($this->name.' '.$this->description);

        return match (true) {
            Str::contains($text, ['foto', 'cetak', 'studio', 'laminating']) => 'images/services/studio-foto.jpg',
            Str::contains($text, ['teh', 'klepon', 'jajanan']) => 'images/services/teh-artisan.jpg',
            Str::contains($text, ['pastry', 'croissant', 'roti', 'kue', 'cake', 'donat']) => 'images/stores/pastry.jpg',
            Str::contains($text, ['matcha', 'jus', 'boba', 'milk tea', 'minuman']) => 'images/stores/kuliner.jpg',
            Str::contains($text, ['kopi', 'coffee', 'espresso', 'americano', 'latte']) => 'images/stores/kopi.jpg',
            Str::contains($text, ['makan', 'nasi', 'ayam', 'mie', 'paket', 'sunda']) => 'images/stores/restoran.jpg',
            Str::contains($text, ['rambut', 'cukur', 'jenggot', 'kumis', 'shaving', 'barber', 'facial', 'creambath', 'manicure', 'eyelash', 'smoothing', 'salon']) => 'images/stores/barbershop.jpg',
            Str::contains($text, ['periksa', 'dokter', 'konsultasi', 'kesehatan', 'klinik']) => 'images/stores/klinik.jpg',
            default => $category?->coverImagePath(),
        };
    }

    /**
     * Public fallback image URL matching the service name, then the category's default cover URL.
     */
    public function fallbackImageUrl(?BusinessCategory $category = null): ?string
    {
        $serviceStockPath = $this->fallbackImagePath(null);

        if ($serviceStockPath !== null) {
            return asset($serviceStockPath);
        }

        return $category?->coverImageUrl();
    }

    /**
     * Scope query to only include active services.
     *
     * @param  Builder<Service>  $query
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
