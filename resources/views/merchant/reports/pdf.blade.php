<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Performa Antrean — {{ $business->name }}</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header layout */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #1E40AF;
            padding-bottom: 10px;
        }

        .header-logo {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .header-logo .brand-dot {
            color: #1E40AF;
        }

        .header-badge {
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            color: #1E40AF;
            background-color: #EFF6FF;
            border: 1px solid #BFDBFE;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 4px;
            text-transform: uppercase;
        }

        .business-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }

        .business-meta {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-title {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-meta {
            text-align: right;
            font-size: 9px;
            color: #475569;
            margin-top: 3px;
        }

        /* KPI Cards Grid */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 14px;
        }

        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            vertical-align: top;
            width: 33.33%;
        }

        .kpi-card.success {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        .kpi-card.warning {
            background-color: #fffbeb;
            border-color: #fde68a;
        }

        .kpi-card.danger {
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.3px;
        }

        .kpi-card.success .kpi-label { color: #166534; }
        .kpi-card.warning .kpi-label { color: #92400e; }
        .kpi-card.danger .kpi-label { color: #991b1b; }

        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
            margin-bottom: 1px;
        }

        .kpi-card.success .kpi-value { color: #14532d; }
        .kpi-card.danger .kpi-value { color: #7f1d1d; }

        .kpi-sub {
            font-size: 7.5px;
            color: #94a3b8;
        }

        /* Section Titles */
        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 14px;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #cbd5e1;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9px;
        }

        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-warning { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-primary { background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-secondary { background-color: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-neutral { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* Peak hour highlight box */
        .info-box {
            background-color: #f8fafc;
            border-left: 3px solid #1E40AF;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8.5px;
            color: #334155;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="header-logo">
                    AntreIn<span class="brand-dot">.</span>
                    <span class="header-badge">UMKM</span>
                </div>
                <div class="business-title">{{ $business->name }}</div>
                <div class="business-meta">
                    Kategori: {{ $business->category?->name ?? 'UMKM' }} &bull; 
                    Kontak: {{ $business->phone ?? '-' }} &bull; 
                    Zona Waktu: {{ $timezone }}
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <div class="doc-title">Laporan Performa Antrean</div>
                <div class="doc-meta">
                    <strong>Periode:</strong> {{ $report['periodLabel'] }}<br>
                    <strong>Rentang:</strong> {{ $report['startDate'] }} s/d {{ $report['endDate'] }}<br>
                    <strong>Waktu Cetak:</strong> {{ $generatedAt }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Operational Summary KPI Cards --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Total Tiket Diterbitkan</div>
                <div class="kpi-value">{{ number_format($report['totalTickets']) }}</div>
                <div class="kpi-sub">Seluruh kanal (Online &amp; Walk-in)</div>
            </td>
            <td class="kpi-card success">
                <div class="kpi-label">Tiket Selesai Dilayani</div>
                <div class="kpi-value">{{ number_format($report['completedCount']) }}</div>
                <div class="kpi-sub">
                    {{ $report['totalTickets'] > 0 ? round(($report['completedCount'] / $report['totalTickets']) * 100, 1) : 0 }}% tingkat penyelesaian
                </div>
            </td>
            <td class="kpi-card danger">
                <div class="kpi-label">Tiket Dibatalkan / Dilewati</div>
                <div class="kpi-value">{{ number_format($report['cancelledCount'] + $report['skippedCount']) }}</div>
                <div class="kpi-sub">{{ $report['skippedCount'] }} dilewati &bull; {{ $report['cancelledCount'] }} dibatalkan</div>
            </td>
        </tr>
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Rata-rata Waktu Tunggu</div>
                <div class="kpi-value">
                    {{ $report['avgWaitingMinutes'] !== null ? number_format($report['avgWaitingMinutes'], 1) . ' mnt' : '—' }}
                </div>
                <div class="kpi-sub">Tiket terbit hingga mulai dilayani ({{ $report['waitingSamples'] }} sampel)</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Rata-rata Durasi Pelayanan</div>
                <div class="kpi-value">
                    {{ $report['avgServiceMinutes'] !== null ? number_format($report['avgServiceMinutes'], 1) . ' mnt' : '—' }}
                </div>
                <div class="kpi-sub">Mulai melayani hingga tiket tuntas ({{ $report['serviceSamples'] }} sampel)</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Jam Sibuk Operasional</div>
                <div class="kpi-value" style="font-size: 13px;">
                    @if ($report['peakHour']['threshold_met'])
                        {{ $report['peakHour']['peak_range'] }}
                    @else
                        Belum Signifikan
                    @endif
                </div>
                <div class="kpi-sub">
                    @if ($report['peakHour']['threshold_met'])
                        Beban puncak: {{ $report['peakHour']['max_count'] }} tiket
                    @else
                        Ambang batas belum tercapai (min. 50 tiket/jam)
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Peak hour & Distribution note --}}
    <div class="info-box">
        <strong>Analisis Jam Sibuk:</strong>
        @if ($report['peakHour']['threshold_met'])
            Beban kedatangan tertinggi berada pada jam <strong>{{ $report['peakHour']['peak_range'] }}</strong> sebanyak <strong>{{ $report['peakHour']['max_count'] }} tiket</strong>.
        @else
            Volume kedatangan antrean tertinggi tercatat pada jam <strong>{{ $report['peakHour']['top_hour_formatted'] ?? '—' }}</strong> sebanyak {{ $report['peakHour']['max_count'] }} tiket. Sesuai standar AntreIn, jam sibuk resmi ditetapkan apabila beban antrean mencapai minimal 50 tiket/jam.
        @endif
        &bull; <strong>Status Aktif Saat Ini:</strong> Menunggu: {{ $report['waitingCount'] }} &bull; Dipanggil: {{ $report['calledCount'] }} &bull; Dilayani: {{ $report['servingCount'] }}.
    </div>

    {{-- Section: Rekapitulasi Berdasarkan Layanan --}}
    <div class="section-header">Rekapitulasi Kinerja Berdasarkan Layanan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 32%;">Nama Layanan</th>
                <th style="width: 14%;" class="text-center">Status Layanan</th>
                <th style="width: 13%;" class="text-center">Total Tiket</th>
                <th style="width: 13%;" class="text-center">Selesai</th>
                <th style="width: 13%;" class="text-center">Dilewati / Batal</th>
                <th style="width: 15%;" class="text-right">Rata-rata Pelayanan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['servicesBreakdown'] as $svc)
                <tr>
                    <td class="font-bold">{{ $svc['service_name'] }}</td>
                    <td class="text-center">
                        @if ($svc['is_active'])
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-neutral">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center font-bold">{{ number_format($svc['total_tickets']) }}</td>
                    <td class="text-center">{{ number_format($svc['completed_tickets']) }}</td>
                    <td class="text-center">{{ number_format($svc['skipped_tickets'] + $svc['cancelled_tickets']) }}</td>
                    <td class="text-right">
                        {{ $svc['avg_service_minutes'] !== null ? number_format($svc['avg_service_minutes'], 1) . ' mnt' : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 12px; color: #94a3b8;">
                        Tidak ada konfigurasi layanan yang ditemukan pada UMKM ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Section: Rincian Tiket Antrean --}}
    @if ($report['hasData'] && count($report['tickets']) > 0)
        <div class="section-header" style="margin-top: 16px;">
            Rincian Tiket Antrean pada Periode (Total: {{ number_format(count($report['tickets'])) }} Tiket)
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">No. Tiket</th>
                    <th style="width: 22%;">Pelanggan</th>
                    <th style="width: 20%;">Layanan</th>
                    <th style="width: 10%;" class="text-center">Sumber</th>
                    <th style="width: 12%;" class="text-center">Status</th>
                    <th style="width: 13%;">Waktu Ambil</th>
                    <th style="width: 13%;" class="text-right">Durasi Layanan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['tickets'] as $ticket)
                    @php
                        $servingDuration = ($ticket->service_started_at && $ticket->completed_at && $ticket->completed_at >= $ticket->service_started_at)
                            ? round($ticket->service_started_at->diffInSeconds($ticket->completed_at) / 60, 1) . ' mnt'
                            : '—';
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $ticket->ticket_number }}</td>
                        <td>{{ $ticket->customer_name }}</td>
                        <td>{{ $ticket->service?->name ?? 'Layanan Dihapus' }}</td>
                        <td class="text-center">
                            @if ($ticket->source === 'online')
                                <span class="badge badge-primary">Online</span>
                            @else
                                <span class="badge badge-secondary">Walk-in</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @switch($ticket->status)
                                @case('selesai')
                                    <span class="badge badge-success">Selesai</span>
                                    @break
                                @case('menunggu')
                                    <span class="badge badge-warning">Menunggu</span>
                                    @break
                                @case('dipanggil')
                                    <span class="badge badge-primary">Dipanggil</span>
                                    @break
                                @case('sedang_dilayani')
                                    <span class="badge badge-secondary">Dilayani</span>
                                    @break
                                @case('dilewati')
                                    <span class="badge badge-neutral">Dilewati</span>
                                    @break
                                @case('dibatalkan')
                                    <span class="badge badge-danger">Batal</span>
                                    @break
                                @default
                                    <span class="badge badge-neutral">{{ $ticket->status }}</span>
                            @endswitch
                        </td>
                        <td>
                            {{ $ticket->created_at ? $ticket->created_at->timezone($timezone)->format('d/m H:i') : '-' }}
                        </td>
                        <td class="text-right">{{ $servingDuration }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Footer note --}}
    <div class="footer">
        Laporan Resmi AntreIn &bull; Platform Manajemen Antrean Digital Terintegrasi UMKM &bull; Dokumen diterbitkan secara otomatis &bull; {{ $generatedAt }}
    </div>
</body>
</html>
