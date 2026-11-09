<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\PlatformSetting;
use App\Models\QueueTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Admin Platform Dashboard with statistics, charts, and activity logs.
     */
    public function index(Request $request): View
    {
        $timezone = PlatformSetting::get('default_timezone', 'Asia/Makassar') ?: 'Asia/Makassar';
        $now = Carbon::now($timezone);

        $period = $request->query('period', '7_days');

        // Determine date range based on selected period
        [$startDate, $endDate, $periodLabel] = match ($period) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Hari Ini ('. $now->toDateString() .')',
            ],
            '30_days' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                '30 Hari Terakhir',
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                'Bulan Ini ('. $now->translatedFormat('F Y') .')',
            ],
            'all' => [
                Carbon::create(2025, 1, 1)->startOfDay(),
                $now->copy()->endOfDay(),
                'Semua Waktu',
            ],
            default => [ // 7_days default
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                '7 Hari Terakhir',
            ],
        };

        // Platform-wide overall metrics (Absolute snapshot)
        $totalBusinesses = Business::count();
        $activeBusinesses = Business::where('is_suspended', false)->count();
        $suspendedBusinesses = Business::where('is_suspended', true)->count();
        $totalUsers = User::count();
        $totalQueues = QueueTicket::count();
        $completedQueues = QueueTicket::where('status', QueueTicket::STATUS_SELESAI)->count();

        // Period-specific metrics
        $periodTicketsQuery = QueueTicket::query()->whereBetween('created_at', [$startDate, $endDate]);
        $periodBusinessesQuery = Business::query()->whereBetween('created_at', [$startDate, $endDate]);

        $periodMetrics = [
            'tickets_count' => (clone $periodTicketsQuery)->count(),
            'businesses_count' => (clone $periodBusinessesQuery)->count(),
            'completed_count' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_SELESAI)->count(),
            'cancelled_count' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_DIBATALKAN)->count(),
        ];

        // Chart C: Distribusi Status Antrean (Status Breakdown in period)
        $statusDistribution = [
            'menunggu' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_MENUNGGU)->count(),
            'dipanggil' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_DIPANGGIL)->count(),
            'sedang_dilayani' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_SEDANG_DILAYANI)->count(),
            'selesai' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_SELESAI)->count(),
            'dilewati' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_DILEWATI)->count(),
            'dibatalkan' => (clone $periodTicketsQuery)->where('status', QueueTicket::STATUS_DIBATALKAN)->count(),
        ];

        // Charts A & B: Daily Trend Series (Pendaftaran UMKM & Tiket Antrean)
        $chartLabels = [];
        $businessTrend = [];
        $queueTrend = [];

        $diffInDays = $startDate->diffInDays($endDate);
        // Cap daily points to max 31 days to ensure fast and clean visualization
        $daysCount = min($diffInDays + 1, 31);
        $pointStart = $endDate->copy()->subDays($daysCount - 1)->startOfDay();

        for ($i = 0; $i < $daysCount; $i++) {
            $day = $pointStart->copy()->addDays($i);
            $dayString = $day->toDateString();
            $chartLabels[] = $day->translatedFormat('d M');

            $businessTrend[] = Business::whereDate('created_at', $dayString)->count();
            $queueTrend[] = QueueTicket::whereDate('created_at', $dayString)->count();
        }

        // Recent Activity Logs from real data
        $recentActivities = ActivityLog::with(['user', 'business'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'totalBusinesses' => $totalBusinesses,
            'activeBusinesses' => $activeBusinesses,
            'suspendedBusinesses' => $suspendedBusinesses,
            'totalUsers' => $totalUsers,
            'totalQueues' => $totalQueues,
            'completedQueues' => $completedQueues,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'periodMetrics' => $periodMetrics,
            'statusDistribution' => $statusDistribution,
            'chartLabels' => $chartLabels,
            'businessTrend' => $businessTrend,
            'queueTrend' => $queueTrend,
            'recentActivities' => $recentActivities,
            'timezone' => $timezone,
        ]);
    }
}

