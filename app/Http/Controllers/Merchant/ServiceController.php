<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Display a listing of services for the merchant's business.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $search = $request->query('search');
        $status = $request->query('status');

        $query = $business->services()
            ->withCount('queueTickets')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false));

        $services = $query->orderBy('name')->paginate(10)->withQueryString();

        $stats = [
            'total' => $business->services()->count(),
            'active' => $business->services()->where('is_active', true)->count(),
            'inactive' => $business->services()->where('is_active', false)->count(),
            'avg_duration' => round($business->services()->avg('duration_minutes') ?? 0),
        ];

        return view('merchant.services.index', [
            'business' => $business,
            'services' => $services,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'daily_quota' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service = $business->services()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'price' => $validated['price'] ?? 0,
            'daily_quota' => $validated['daily_quota'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::log(
            action: 'service_created',
            description: sprintf('Layanan baru "%s" ditambahkan', $service->name),
            business: $business,
            user: $user,
            properties: ['service_id' => $service->id]
        );

        return redirect()->route('merchant.services.index')
            ->with('status', sprintf('Layanan "%s" berhasil ditambahkan!', $service->name));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        // Tenant ownership check
        if ($service->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah layanan ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'daily_quota' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'price' => $validated['price'] ?? 0,
            'daily_quota' => $validated['daily_quota'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::log(
            action: 'service_updated',
            description: sprintf('Layanan "%s" berhasil diperbarui', $service->name),
            business: $business,
            user: $user,
            properties: ['service_id' => $service->id]
        );

        return redirect()->route('merchant.services.index')
            ->with('status', sprintf('Layanan "%s" berhasil diperbarui!', $service->name));
    }

    /**
     * Toggle the active status of the service.
     */
    public function toggleStatus(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($service->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah layanan ini.');
        }

        $service->is_active = ! $service->is_active;
        $service->save();

        ActivityLog::log(
            action: 'service_status_toggled',
            description: sprintf(
                'Status layanan "%s" diubah menjadi %s',
                $service->name,
                $service->is_active ? 'Aktif' : 'Nonaktif'
            ),
            business: $business,
            user: $user,
            properties: ['service_id' => $service->id, 'is_active' => $service->is_active]
        );

        return back()->with('status', sprintf(
            'Layanan "%s" sekarang %s.',
            $service->name,
            $service->is_active ? 'diaktifkan (dapat dipilih pelanggan)' : 'dinonaktifkan (disembunyikan dari pilihan antrean baru)'
        ));
    }

    /**
     * Remove the specified service from storage using soft deletion.
     * Preserves historical ticket references intact.
     */
    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($service->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus layanan ini.');
        }

        $serviceName = $service->name;
        $ticketCount = $service->queueTickets()->count();

        // Soft delete the service
        $service->delete();

        ActivityLog::log(
            action: 'service_deleted',
            description: sprintf('Layanan "%s" dihapus (soft-delete, %d riwayat tiket dipertahankan)', $serviceName, $ticketCount),
            business: $business,
            user: $user,
            properties: ['service_id' => $service->id, 'historical_tickets' => $ticketCount]
        );

        return redirect()->route('merchant.services.index')
            ->with('status', sprintf('Layanan "%s" berhasil dihapus. Riwayat tiket terdahulu tetap dipertahankan.', $serviceName));
    }
}

