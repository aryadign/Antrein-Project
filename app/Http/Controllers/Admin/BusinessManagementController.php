<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\QueueTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessManagementController extends Controller
{
    /**
     * Display a listing of all registered businesses with search, filters, and metrics.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $status = $request->query('status'); // 'all', 'active', 'suspended'

        $query = Business::query()
            ->with(['category', 'user'])
            ->withCount(['services', 'queueTickets']);

        // Search filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Category filter
        if ($categoryId) {
            $query->where('category_id', (int) $categoryId);
        }

        // Platform suspension status filter
        if ($status === 'active') {
            $query->where('is_suspended', false);
        } elseif ($status === 'suspended') {
            $query->where('is_suspended', true);
        }

        $businesses = $query->latest('id')->paginate(10)->withQueryString();

        $categories = BusinessCategory::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total' => Business::count(),
            'active' => Business::where('is_suspended', false)->count(),
            'suspended' => Business::where('is_suspended', true)->count(),
        ];

        return view('admin.businesses.index', [
            'businesses' => $businesses,
            'categories' => $categories,
            'stats' => $stats,
            'search' => $search,
            'categoryId' => $categoryId,
            'status' => $status,
        ]);
    }

    /**
     * Display the comprehensive details of a specific business.
     */
    public function show(Business $business): View
    {
        $business->load([
            'category',
            'user',
            'services' => fn ($q) => $q->orderBy('name'),
            'hours' => fn ($q) => $q->orderBy('day_of_week'),
        ]);

        // Aggregate queue statistics for this business
        $queueStats = [
            'total' => QueueTicket::where('business_id', $business->id)->count(),
            'menunggu' => QueueTicket::where('business_id', $business->id)->where('status', QueueTicket::STATUS_MENUNGGU)->count(),
            'dipanggil' => QueueTicket::where('business_id', $business->id)->where('status', QueueTicket::STATUS_DIPANGGIL)->count(),
            'sedang_dilayani' => QueueTicket::where('business_id', $business->id)->where('status', QueueTicket::STATUS_SEDANG_DILAYANI)->count(),
            'selesai' => QueueTicket::where('business_id', $business->id)->where('status', QueueTicket::STATUS_SELESAI)->count(),
            'dilewati' => QueueTicket::where('business_id', $business->id)->where('status', QueueTicket::STATUS_DILEWATI)->count(),
            'dibatalkan' => QueueTicket::where('business_id', $business->id)->where('status', QueueTicket::STATUS_DIBATALKAN)->count(),
        ];

        // Recent audit activity logs for this business
        $recentLogs = ActivityLog::with('user')
            ->where('business_id', $business->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.businesses.show', [
            'business' => $business,
            'queueStats' => $queueStats,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Toggle platform suspension for a business account.
     * Does NOT alter daily operational status (Buka/Jeda/Tutup) directly.
     */
    public function toggleSuspension(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previousState = $business->is_suspended;
        $newState = ! $previousState;

        $business->is_suspended = $newState;
        $business->save();

        $action = $newState ? 'business_suspended' : 'business_reactivated';
        $desc = $newState
            ? sprintf('Akun UMKM "%s" ditangguhkan (Suspended) oleh administrator platform.', $business->name)
            : sprintf('Penangguhan akun UMKM "%s" dicabut oleh administrator platform.', $business->name);

        ActivityLog::log(
            action: $action,
            description: $desc,
            business: $business,
            user: $request->user(),
            properties: [
                'previous_state' => $previousState,
                'new_state' => $newState,
                'reason' => $validated['reason'] ?? null,
            ]
        );

        $flashMessage = $newState
            ? sprintf('UMKM "%s" berhasil ditangguhkan. Pendaftaran tiket antrean baru diblokir.', $business->name)
            : sprintf('Penangguhan UMKM "%s" berhasil dicabut. Operasional normal dipulihkan.', $business->name);

        return back()->with('status', $flashMessage);
    }
}

