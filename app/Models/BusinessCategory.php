<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessCategory extends Model
{
    use HasFactory;

    /**
     * Customer-facing colour classes per category theme: soft background, border, icon tint and text colour.
     *
     * @var array<string, array{background: string, border: string, tint: string, text: string}>
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
     * Human-readable Indonesian labels for each customer colour theme.
     *
     * @var array<string, string>
     */
    public const array COLOR_THEME_LABELS = [
        'barbershop' => 'Hijau',
        'salon' => 'Pink',
        'klinik' => 'Biru',
        'kuliner' => 'Kuning',
        'jasa' => 'Ungu',
        'umum' => 'Biru brand',
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
        'icon',
        'color_theme',
        'cover_path',
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
        $icons = config('category_icons', []);

        if (is_string($this->icon) && $this->icon !== '' && isset($icons[$this->icon]['path'])) {
            return (string) $icons[$this->icon]['path'];
        }

        return match ($this->guessedCustomerTheme()) {
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
     * Public URL of the default cover photo for stores in this category:
     * admin-uploaded cover first, otherwise the stock photo matching the category slug.
     */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }

        $stockPath = $this->coverImagePath();

        return $stockPath ? asset($stockPath) : null;
    }

    /**
     * Stock photo used as the visual fallback for stores in this category (null when none fits).
     */
    public function coverImagePath(): ?string
    {
        return match ($this->guessedCustomerTheme()) {
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
        if (is_string($this->color_theme) && array_key_exists($this->color_theme, self::CUSTOMER_PALETTES)) {
            return $this->color_theme;
        }

        return $this->guessedCustomerTheme();
    }

    /**
     * Fallback visual theme guessed from the category slug when no explicit theme is stored.
     */
    public function guessedCustomerTheme(): string
    {
        return match (true) {
            Str::contains($this->slug, ['barber', 'pangkas', 'rambut']) => 'barbershop',
            Str::contains($this->slug, ['salon', 'kecantikan', 'spa']) => 'salon',
            Str::contains($this->slug, ['klinik', 'dokter', 'kesehatan']) => 'klinik',
            Str::contains($this->slug, ['kuliner', 'kafe', 'kopi', 'makan', 'minuman', 'resto']) => 'kuliner',
            Str::contains($this->slug, ['jasa', 'servis', 'service', 'elektronik', 'bengkel', 'otomotif', 'motor', 'mobil', 'laundry']) => 'jasa',
            default => 'umum',
        };
    }
}
