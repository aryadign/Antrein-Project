<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BusinessCategory extends Model
{
    use HasFactory;

    /**
     * Customer-facing colour classes per category theme: soft background, icon tint and text colour.
     *
     * @var array<string, array{background: string, tint: string, text: string}>
     */
    public const array CUSTOMER_PALETTES = [
        'barbershop' => ['background' => 'bg-emerald-100', 'border' => 'border-emerald-200', 'tint' => 'bg-emerald-600', 'text' => 'text-emerald-800'],
        'salon' => ['background' => 'bg-pink-100', 'border' => 'border-pink-200', 'tint' => 'bg-pink-500', 'text' => 'text-pink-700'],
        'klinik' => ['background' => 'bg-sky-100', 'border' => 'border-sky-200', 'tint' => 'bg-sky-600', 'text' => 'text-sky-800'],
        'kuliner' => ['background' => 'bg-amber-100', 'border' => 'border-amber-200', 'tint' => 'bg-amber-600', 'text' => 'text-amber-800'],
        'jasa' => ['background' => 'bg-indigo-100', 'border' => 'border-indigo-200', 'tint' => 'bg-indigo-500', 'text' => 'text-indigo-700'],
        'umum' => ['background' => 'bg-brand-100', 'border' => 'border-brand-200', 'tint' => 'bg-brand-700', 'text' => 'text-brand-700'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
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
     * Scope query to only include active business categories.
     *
     * @param  Builder<BusinessCategory>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all businesses categorized under this taxonomy.
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class, 'category_id');
    }

    /**
     * Short label for customer chips, e.g. "Kuliner" for "Kuliner & Kafe".
     */
    public function shortName(): string
    {
        return Str::before($this->name, ' &');
    }

    /**
     * Public asset path of the icon that represents this category for customers.
     */
    public function iconPath(): string
    {
        return match ($this->customerTheme()) {
            'barbershop' => 'images/icons/scissors.svg',
            'salon' => 'images/icons/sparkles.svg',
            'klinik' => 'images/icons/stethoscope.svg',
            'kuliner' => Str::contains($this->slug, ['kopi', 'kafe']) && ! Str::contains($this->slug, 'kuliner')
                ? 'images/figma/coffee.svg'
                : 'images/figma/utensils.svg',
            'jasa' => 'images/icons/wrench.svg',
            default => 'images/figma/store.svg',
        };
    }

    /**
     * Stock photo used as the visual fallback for stores in this category (null when none fits).
     */
    public function coverImagePath(): ?string
    {
        return match ($this->customerTheme()) {
            'barbershop' => 'images/stores/barbershop.jpg',
            'klinik' => 'images/stores/klinik.jpg',
            'kuliner' => Str::contains($this->slug, ['kopi', 'kafe']) ? 'images/stores/kopi.jpg' : 'images/stores/restoran.jpg',
            default => null,
        };
    }

    /**
     * Colour classes for this category's customer-facing visuals.
     *
     * @return array{background: string, border: string, tint: string, text: string}
     */
    public function customerPalette(): array
    {
        return self::CUSTOMER_PALETTES[$this->customerTheme()];
    }

    /**
     * Visual theme key used to colour store covers and chips of this category.
     */
    public function customerTheme(): string
    {
        return match (true) {
            Str::contains($this->slug, ['barber', 'pangkas', 'rambut']) => 'barbershop',
            Str::contains($this->slug, ['salon', 'kecantikan', 'spa']) => 'salon',
            Str::contains($this->slug, ['klinik', 'dokter', 'kesehatan']) => 'klinik',
            Str::contains($this->slug, ['kuliner', 'kafe', 'kopi', 'makan', 'minuman', 'resto']) => 'kuliner',
            Str::contains($this->slug, ['jasa', 'servis', 'elektronik']) => 'jasa',
            default => 'umum',
        };
    }
}
