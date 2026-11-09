<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Services\MerchantReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(
        protected MerchantReportService $reportService
    ) {}

    /**
     * Export the merchant queue operational report as a downloadable CSV spreadsheet (P1-06).
     */
    public function csv(Request $request): StreamedResponse|RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $period = (string) $request->query('period', '7_days');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (! in_array($period, ['today', '7_days', '30_days', 'custom'], true)) {
            $period = '7_days';
        }

        try {
            $report = $this->reportService->getReportData(
                business: $business,
                period: $period,
                startDate: $startDate ? (string) $startDate : null,
                endDate: $endDate ? (string) $endDate : null,
                includeTickets: true
            );
        } catch (DomainException $e) {
            return redirect()->route('merchant.reports', ['period' => '7_days'])
                ->withErrors(['date_range' => $e->getMessage()]);
        }

        $businessSlug = Str::slug($business->name) ?: 'umkm';
        $filename = sprintf(
            'laporan-antrean-%s-%s-sd-%s.csv',
            $businessSlug,
            $report['startDate'],
            $report['endDate']
        );

        $timezone = $report['timezone'];
        $nowFormatted = Carbon::now($timezone)->translatedFormat('d F Y H:i:s') . ' ' . $timezone;

        return response()->streamDownload(function () use ($business, $report, $timezone, $nowFormatted) {
            $handle = fopen('php://output', 'w');

            // 1. Output UTF-8 Byte Order Mark (BOM) for seamless Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // 2. Executive Header & Metadata Section
            fputcsv($handle, ['=== LAPORAN PERFORMA ANTREAN — ANTREIN ===']);
            fputcsv($handle, ['Nama UMKM', self::sanitizeCsvValue($business->name)]);
            fputcsv($handle, ['Kategori', self::sanitizeCsvValue($business->category?->name ?? 'UMKM')]);
            fputcsv($handle, ['Periode Laporan', self::sanitizeCsvValue($report['periodLabel'])]);
            fputcsv($handle, ['Rentang Tanggal', self::sanitizeCsvValue($report['startDate'] . ' s/d ' . $report['endDate'])]);
            fputcsv($handle, ['Zona Waktu Operasional', self::sanitizeCsvValue($timezone)]);
            fputcsv($handle, ['Waktu Pengunduhan', self::sanitizeCsvValue($nowFormatted)]);
            fputcsv($handle, []);

            // 3. Operational Summary Metrics Section (M9-02 to M9-05, M9-09)
            fputcsv($handle, ['=== RINGKASAN METRIK OPERASIONAL ===']);
            fputcsv($handle, ['Metrik', 'Nilai', 'Keterangan']);
            fputcsv($handle, ['Total Tiket Diterbitkan', $report['totalTickets'], 'Seluruh tiket antrean pada periode']);
            fputcsv($handle, ['Tiket Selesai Dilayani', $report['completedCount'], 'Pelayanan tuntas']);
            fputcsv($handle, ['Tiket Sedang Menunggu', $report['waitingCount'], 'Masih dalam antrean']);
            fputcsv($handle, ['Tiket Sedang Dipanggil', $report['calledCount'], 'Menunggu konfirmasi kedatangan']);
            fputcsv($handle, ['Tiket Sedang Dilayani', $report['servingCount'], 'Proses pelayanan berlangsung']);
            fputcsv($handle, ['Tiket Dilewati', $report['skippedCount'], 'Pelanggan tidak hadir']);
            fputcsv($handle, ['Tiket Dibatalkan', $report['cancelledCount'], 'Pembatalan sebelum pelayanan']);
            fputcsv($handle, [
                'Rata-rata Waktu Tunggu (Menit)',
                $report['avgWaitingMinutes'] !== null ? number_format($report['avgWaitingMinutes'], 1) : 'Belum ada data',
                'Dihitung dari tiket dibuat hingga mulai dilayani',
            ]);
            fputcsv($handle, [
                'Rata-rata Durasi Pelayanan (Menit)',
                $report['avgServiceMinutes'] !== null ? number_format($report['avgServiceMinutes'], 1) : 'Belum ada data',
                'Dihitung dari mulai dilayani hingga selesai',
            ]);

            $peakHourText = $report['peakHour']['threshold_met']
                ? $report['peakHour']['peak_range'] . ' (' . $report['peakHour']['max_count'] . ' tiket)'
                : 'Belum mencapai ambang batas (minimal 50 tiket/jam)';
            fputcsv($handle, ['Jam Sibuk (Puncak Antrean)', self::sanitizeCsvValue($peakHourText), 'Berdasarkan waktu kedatangan pelanggan']);
            fputcsv($handle, []);

            // 4. Per-Service Breakdown Section (M9-08)
            fputcsv($handle, ['=== REKAPITULASI BERDASARKAN LAYANAN ===']);
            fputcsv($handle, [
                'Nama Layanan',
                'Status Layanan',
                'Total Tiket',
                'Selesai',
                'Dilewati',
                'Dibatalkan',
                'Rata-rata Pelayanan (Menit)',
            ]);

            foreach ($report['servicesBreakdown'] as $svc) {
                fputcsv($handle, [
                    self::sanitizeCsvValue($svc['service_name']),
                    $svc['is_active'] ? 'Aktif' : 'Nonaktif',
                    $svc['total_tickets'],
                    $svc['completed_tickets'],
                    $svc['skipped_tickets'],
                    $svc['cancelled_tickets'],
                    $svc['avg_service_minutes'] !== null ? number_format($svc['avg_service_minutes'], 1) : '-',
                ]);
            }
            fputcsv($handle, []);

            // 5. Individual Queue Ticket Details Section
            fputcsv($handle, ['=== RINCIAN TIKET ANTREAN PADA PERIODE ===']);
            fputcsv($handle, [
                'Nomor Tiket',
                'Nama / Label Pelanggan',
                'Layanan',
                'Sumber',
                'Status',
                'Waktu Dibuat',
                'Mulai Dilayani',
                'Waktu Selesai',
                'Waktu Tunggu (Menit)',
                'Durasi Layanan (Menit)',
            ]);

            /** @var QueueTicket $ticket */
            foreach ($report['tickets'] as $ticket) {
                $waitingMins = ($ticket->created_at && $ticket->service_started_at && $ticket->service_started_at >= $ticket->created_at)
                    ? round($ticket->created_at->diffInSeconds($ticket->service_started_at) / 60, 1)
                    : '-';

                $servingMins = ($ticket->service_started_at && $ticket->completed_at && $ticket->completed_at >= $ticket->service_started_at)
                    ? round($ticket->service_started_at->diffInSeconds($ticket->completed_at) / 60, 1)
                    : '-';

                fputcsv($handle, [
                    self::sanitizeCsvValue($ticket->ticket_number),
                    self::sanitizeCsvValue($ticket->customer_name),
                    self::sanitizeCsvValue($ticket->service?->name ?? 'Layanan Dihapus'),
                    $ticket->source === QueueTicket::SOURCE_ONLINE ? 'Online' : 'Walk-in',
                    self::formatTicketStatus($ticket->status),
                    $ticket->created_at ? $ticket->created_at->timezone($timezone)->format('Y-m-d H:i') : '-',
                    $ticket->service_started_at ? $ticket->service_started_at->timezone($timezone)->format('Y-m-d H:i') : '-',
                    $ticket->completed_at ? $ticket->completed_at->timezone($timezone)->format('Y-m-d H:i') : '-',
                    $waitingMins,
                    $servingMins,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Export the merchant queue operational report as a downloadable PDF document (P1-07).
     */
    public function pdf(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $period = (string) $request->query('period', '7_days');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (! in_array($period, ['today', '7_days', '30_days', 'custom'], true)) {
            $period = '7_days';
        }

        try {
            $report = $this->reportService->getReportData(
                business: $business,
                period: $period,
                startDate: $startDate ? (string) $startDate : null,
                endDate: $endDate ? (string) $endDate : null,
                includeTickets: true
            );
        } catch (DomainException $e) {
            return redirect()->route('merchant.reports', ['period' => '7_days'])
                ->withErrors(['date_range' => $e->getMessage()]);
        }

        $businessSlug = Str::slug($business->name) ?: 'umkm';
        $filename = sprintf(
            'laporan-antrean-%s-%s-sd-%s.pdf',
            $businessSlug,
            $report['startDate'],
            $report['endDate']
        );

        $timezone = $report['timezone'];
        $generatedAt = Carbon::now($timezone)->translatedFormat('d F Y H:i') . ' ' . $timezone;

        $pdf = Pdf::loadView('merchant.reports.pdf', [
            'business' => $business,
            'report' => $report,
            'timezone' => $timezone,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf->download($filename);
    }

    /**
     * Sanitize cell values against CSV Formula Injection (OWASP mitigation).
     * Strings beginning with =, +, -, @, tab, or newline (even if preceded by spaces)
     * are prepended with a single quote (') to force literal interpretation in spreadsheets.
     */
    public static function sanitizeCsvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        if (preg_match('/^\s*[=\+\-@\t\r]/', $str)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Map database status code to human-readable Indonesian label.
     */
    private static function formatTicketStatus(string $status): string
    {
        return match ($status) {
            QueueTicket::STATUS_MENUNGGU => 'Menunggu',
            QueueTicket::STATUS_DIPANGGIL => 'Dipanggil',
            QueueTicket::STATUS_SEDANG_DILAYANI => 'Sedang Dilayani',
            QueueTicket::STATUS_SELESAI => 'Selesai',
            QueueTicket::STATUS_DILEWATI => 'Dilewati',
            QueueTicket::STATUS_DIBATALKAN => 'Dibatalkan',
            default => ucfirst($status),
        };
    }
}
