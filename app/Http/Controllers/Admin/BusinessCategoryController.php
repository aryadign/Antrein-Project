<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BusinessCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'total_businesses' => \App\Models\Business::whereNotNull('category_id')->count(),
        ];

        return view('admin.categories.index', [
            'categories' => $categories,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:business_categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
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

        $category = BusinessCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::log(
            action: 'category_created',
            description: sprintf('Kategori UMKM baru "%s" dibuat', $category->name),
            business: null,
            user: $request->user(),
            properties: ['category_id' => $category->id, 'slug' => $category->slug]
        );

        return redirect()->route('admin.categories.index')
            ->with('status', 'Kategori baru berhasil ditambahkan!');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, BusinessCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('business_categories', 'slug')->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::log(
            action: 'category_updated',
            description: sprintf('Kategori UMKM "%s" diperbarui', $category->name),
            business: null,
            user: $request->user(),
            properties: ['category_id' => $category->id]
        );

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
     * Enforces the historical protection rule: Referenced categories cannot be hard-deleted.
     */
    public function destroy(Request $request, BusinessCategory $category): RedirectResponse
    {
        $businessCount = $category->businesses()->count();

        if ($businessCount > 0) {
            return back()->withErrors([
                'delete_error' => sprintf(
                    'Kategori "%s" tidak dapat dihapus karena masih digunakan oleh %d UMKM terdaftar. Silakan nonaktifkan kategori ini jika tidak ingin digunakan oleh UMKM baru.',
                    $category->name,
                    $businessCount
                ),
            ]);
        }

        $categoryName = $category->name;
        $category->delete();

        ActivityLog::log(
            action: 'category_deleted',
            description: sprintf('Kategori UMKM "%s" dihapus', $categoryName),
            business: null,
            user: $request->user(),
            properties: ['category_id' => $category->id]
        );

        return redirect()->route('admin.categories.index')
            ->with('status', sprintf('Kategori "%s" berhasil dihapus.', $categoryName));
    }
}

