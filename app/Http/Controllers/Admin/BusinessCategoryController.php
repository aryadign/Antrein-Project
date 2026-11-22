<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessCategoryController extends Controller
{
    /**
     * Display a listing of business categories.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = BusinessCategory::query()
            ->withCount('businesses')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false));

        $categories = $query->orderBy('name')->paginate(10)->withQueryString();

        $stats = [
            'total' => BusinessCategory::count(),
            'active' => BusinessCategory::where('is_active', true)->count(),
            'inactive' => BusinessCategory::where('is_active', false)->count(),
            'total_businesses' => Business::whereNotNull('category_id')->count(),
        ];

        $categoryIcons = collect(config('category_icons', []))
            ->map(fn (array $item, string $key): array => [
                'key' => $key,
                'label' => $item['label'],
                'path' => $item['path'],
                'url' => asset($item['path']),
            ])
            ->all();

        $colorThemes = collect(BusinessCategory::CUSTOMER_PALETTES)
            ->map(fn (array $palette, string $key): array => [
                'key' => $key,
                'label' => BusinessCategory::COLOR_THEME_LABELS[$key] ?? Str::headline($key),
                'background' => $palette['background'],
                'border' => $palette['border'],
                'tint' => $palette['tint'],
                'text' => $palette['text'],
            ])
            ->all();

        $allCategories = BusinessCategory::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (BusinessCategory $cat): array => [
                'id' => $cat->id,
                'name' => $cat->name,
                'is_active' => $cat->is_active,
            ])
            ->all();

        return view('admin.categories.index', [
            'categories' => $categories,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'categoryIcons' => $categoryIcons,
            'colorThemes' => $colorThemes,
            'allCategories' => $allCategories,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $iconKeys = array_keys(config('category_icons', []));
        $colorThemeKeys = array_keys(BusinessCategory::CUSTOMER_PALETTES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:business_categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', Rule::in($iconKeys)],
            'color_theme' => ['nullable', 'string', Rule::in($colorThemeKeys)],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'icon.in' => 'Ikon kategori yang dipilih tidak valid.',
            'color_theme.in' => 'Tema warna kategori yang dipilih tidak valid.',
            'cover.image' => 'Foto sampul kategori harus berupa berkas gambar.',
            'cover.mimes' => 'Foto sampul kategori harus berformat JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran foto sampul kategori maksimal 4 MB.',
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug if collision occurs
        $baseSlug = $slug;
        $counter = 1;
        while (BusinessCategory::where('slug', $slug)->exists()) {
            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }

        $newCoverPath = $request->hasFile('cover')
            ? $request->file('cover')->store('category-covers', 'public')
            : null;

        try {
            DB::transaction(function () use ($request, $validated, $slug, $newCoverPath): void {
                $category = BusinessCategory::create([
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,
                    'icon' => $validated['icon'] ?? null,
                    'color_theme' => $validated['color_theme'] ?? null,
                    'cover_path' => $newCoverPath,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                ActivityLog::log(
                    action: 'category_created',
                    description: sprintf('Kategori UMKM baru "%s" dibuat', $category->name),
                    business: null,
                    user: $request->user(),
                    properties: [
                        'category_id' => $category->id,
                        'slug' => $category->slug,
                        'icon' => $category->icon,
                        'color_theme' => $category->color_theme,
                    ]
                );
            });
        } catch (\Throwable $exception) {
            if ($newCoverPath !== null) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $exception;
        }

        return redirect()->route('admin.categories.index')
            ->with('status', 'Kategori baru berhasil ditambahkan!');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, BusinessCategory $category): RedirectResponse
    {
        $iconKeys = array_keys(config('category_icons', []));
        $colorThemeKeys = array_keys(BusinessCategory::CUSTOMER_PALETTES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('business_categories', 'slug')->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', Rule::in($iconKeys)],
            'color_theme' => ['nullable', 'string', Rule::in($colorThemeKeys)],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'icon.in' => 'Ikon kategori yang dipilih tidak valid.',
            'color_theme.in' => 'Tema warna kategori yang dipilih tidak valid.',
            'cover.image' => 'Foto sampul kategori harus berupa berkas gambar.',
            'cover.mimes' => 'Foto sampul kategori harus berformat JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran foto sampul kategori maksimal 4 MB.',
        ]);

        $oldCoverPath = $category->cover_path;
        $removeCover = $request->boolean('remove_cover');
        $newCoverPath = $request->hasFile('cover')
            ? $request->file('cover')->store('category-covers', 'public')
            : null;

        $coverPath = $oldCoverPath;
        if ($newCoverPath !== null) {
            $coverPath = $newCoverPath;
        } elseif ($removeCover) {
            $coverPath = null;
        }

        try {
            DB::transaction(function () use ($request, $category, $validated, $coverPath): void {
                $category->update([
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['slug']),
                    'description' => $validated['description'] ?? null,
                    'icon' => $validated['icon'] ?? null,
                    'color_theme' => $validated['color_theme'] ?? null,
                    'cover_path' => $coverPath,
                    'is_active' => $request->boolean('is_active'),
                ]);

                ActivityLog::log(
                    action: 'category_updated',
                    description: sprintf('Kategori UMKM "%s" diperbarui', $category->name),
                    business: null,
                    user: $request->user(),
                    properties: [
                        'category_id' => $category->id,
                        'icon' => $category->icon,
                        'color_theme' => $category->color_theme,
                    ]
                );
            });
        } catch (\Throwable $exception) {
            if ($newCoverPath !== null) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $exception;
        }

        if ($oldCoverPath !== null && ($newCoverPath !== null || $removeCover)) {
            Storage::disk('public')->delete($oldCoverPath);
        }

        return redirect()->route('admin.categories.index')
            ->with('status', 'Kategori berhasil diperbarui!');
    }

    /**
     * Toggle the active status of the specified category.
     */
    public function toggleStatus(Request $request, BusinessCategory $category): RedirectResponse
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        ActivityLog::log(
            action: 'category_status_toggled',
            description: sprintf(
                'Status kategori "%s" diubah menjadi %s',
                $category->name,
                $category->is_active ? 'Aktif' : 'Nonaktif'
            ),
            business: null,
            user: $request->user(),
            properties: ['category_id' => $category->id, 'is_active' => $category->is_active]
        );

        return back()->with('status', sprintf(
            'Kategori "%s" sekarang %s.',
            $category->name,
            $category->is_active ? 'diaktifkan' : 'dinonaktifkan'
        ));
    }

    /**
     * Remove the specified category from storage.
     * Referenced categories require a valid replacement_category_id so all businesses
     * are reassigned atomically before deletion.
     */
    public function destroy(Request $request, BusinessCategory $category): RedirectResponse
    {
        $businessCount = $category->businesses()->count();
        $replacementCategory = null;

        if ($businessCount > 0) {
            if (! $request->filled('replacement_category_id')) {
                return back()->withErrors([
                    'delete_error' => sprintf(
                        'Kategori "%s" tidak dapat dihapus karena masih digunakan oleh %d UMKM terdaftar. Pilih kategori pengganti terlebih dahulu untuk memindahkan UMKM.',
                        $category->name,
                        $businessCount
                    ),
                ]);
            }

            $validated = $request->validate([
                'replacement_category_id' => [
                    'required',
                    'integer',
                    Rule::exists('business_categories', 'id'),
                    Rule::notIn([$category->id]),
                ],
            ], [
                'replacement_category_id.required' => 'Kategori pengganti wajib dipilih.',
                'replacement_category_id.exists' => 'Kategori pengganti tidak ditemukan.',
                'replacement_category_id.not_in' => 'Kategori pengganti tidak boleh sama dengan kategori yang akan dihapus.',
            ]);

            $replacementCategory = BusinessCategory::findOrFail((int) $validated['replacement_category_id']);
        }

        $categoryId = $category->id;
        $categoryName = $category->name;
        $oldCoverPath = $category->cover_path;
        $movedCount = 0;

        DB::transaction(function () use ($request, $category, $categoryId, $categoryName, $replacementCategory, &$movedCount): void {
            if ($replacementCategory !== null) {
                $movedCount = Business::query()
                    ->where('category_id', $category->id)
                    ->update(['category_id' => $replacementCategory->id]);
            }

            $category->delete();

            ActivityLog::log(
                action: 'category_deleted',
                description: $replacementCategory !== null
                    ? sprintf(
                        'Kategori UMKM "%s" dihapus dan %d UMKM dipindahkan ke "%s"',
                        $categoryName,
                        $movedCount,
                        $replacementCategory->name
                    )
                    : sprintf('Kategori UMKM "%s" dihapus', $categoryName),
                business: null,
                user: $request->user(),
                properties: array_filter([
                    'category_id' => $categoryId,
                    'moved_businesses_count' => $replacementCategory !== null ? $movedCount : null,
                    'replacement_category_id' => $replacementCategory?->id,
                    'replacement_category_name' => $replacementCategory?->name,
                ], fn (mixed $value): bool => $value !== null)
            );
        });

        if ($oldCoverPath !== null) {
            Storage::disk('public')->delete($oldCoverPath);
        }

        $statusMessage = $replacementCategory !== null
            ? sprintf(
                'Kategori "%s" berhasil dihapus dan %d UMKM dipindahkan ke "%s".',
                $categoryName,
                $movedCount,
                $replacementCategory->name
            )
            : sprintf('Kategori "%s" berhasil dihapus.', $categoryName);

        return redirect()->route('admin.categories.index')
            ->with('status', $statusMessage);
    }
}
