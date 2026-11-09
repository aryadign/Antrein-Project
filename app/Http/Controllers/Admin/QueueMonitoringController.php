<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueMonitoringController extends Controller
{
    /**
     * Display a read-only cross-tenant listing of all queue tickets on the platform.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $source = $request->query('source');
        $businessId = $request->query('business_id');
        $date = $request->query('date');

        $query = QueueTicket::query()
            ->with(['business', 'service']);

        // Search by ticket number, customer name, or business name
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('business', function ($b) use ($search) {
                        $b->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status && in_array($status, [
            QueueTicket::STATUS_MENUNGGU,
            QueueTicket::STATUS_DIPANGGIL,
            QueueTicket::STATUS_SEDANG_DILAYANI,
            QueueTicket::STATUS_SELESAI,
            QueueTicket::STATUS_DILEWATI,
            QueueTicket::STATUS_DIBATALKAN,
        ])) {
            $query->where('status', $status);
        }

        // Filter by source
        if ($source && in_array($source, ['online', 'walk_in'])) {
            $query->where('source', $source);
        }

        // Filter by specific business
        if ($businessId) {
            $query->where('business_id', (int) $businessId);
        }

        // Filter by date
        if ($date && $date !== 'all') {
            $query->whereHas('dailyQueue', function ($dq) use ($date) {
                $dq->whereDate('queue_date', $date);
            });
        }

        $tickets = $query->latest('id')->paginate(15)->withQueryString();

        $businesses = Business::orderBy('name')->get();

        $stats = [
            'total' => QueueTicket::count(),
            'menunggu' => QueueTicket::where('status', QueueTicket::STATUS_MENUNGGU)->count(),
            'dipanggil' => QueueTicket::where('status', QueueTicket::STATUS_DIPANGGIL)->count(),
            'sedang_dilayani' => QueueTicket::where('status', QueueTicket::STATUS_SEDANG_DILAYANI)->count(),
            'selesai' => QueueTicket::where('status', QueueTicket::STATUS_SELESAI)->count(),
            'dilewati' => QueueTicket::where('status', QueueTicket::STATUS_DILEWATI)->count(),
            'dibatalkan' => QueueTicket::where('status', QueueTicket::STATUS_DIBATALKAN)->count(),
        ];

        return view('admin.queues.index', [
            'tickets' => $tickets,
            'businesses' => $businesses,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'source' => $source,
            'businessId' => $businessId,
            'date' => $date,
        ]);
    }

    /**
     * Display a strictly read-only view of a queue ticket with masked customer PII.
     */
    public function show(QueueTicket $ticket): View
    {
        $ticket->load([
            'business.category',
            'service',
            'dailyQueue',
            'statusHistories.actor',
        ]);

        return view('admin.queues.show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Helper to mask customer phone number according to OQ-14 privacy guidelines.
     */
    public static function maskPhoneNumber(?string $phone): string
    {
        if (! $phone || trim($phone) === '') {
            return '—';
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($clean);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        if ($length <= 8) {
            return substr($clean, 0, 2) . str_repeat('*', $length - 4) . substr($clean, -2);
        }

        // Standard Indonesian numbers (10-13 digits): show first 4 and last 4, mask middle
        return substr($clean, 0, 4) . '****' . substr($clean, -4);
    }
}

