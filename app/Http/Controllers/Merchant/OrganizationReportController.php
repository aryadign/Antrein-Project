<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\MerchantReportService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationReportController extends Controller
{
    public function __construct(
        protected MerchantReportService $reportService
    ) {}

    /**
     * Display per-branch and aggregated organization analytics.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $organization = $user->managedOrganization();

        if (! $organization) {
            abort(403, 'Anda tidak memiliki hak untuk melihat laporan organisasi.');
        }

        $period = (string) $request->query('period', '7_days');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (! in_array($period, ['today', '7_days', '30_days', 'custom'], true)) {
            $period = '7_days';
        }

        $branches = $organization->branches()
            ->orderBy('name')
            ->get();

        try {
            $summary = $this->reportService->getOrganizationSummary(
                branches: $branches,
                period: $period,
                startDate: $startDate ? (string) $startDate : null,
                endDate: $endDate ? (string) $endDate : null
            );
        } catch (DomainException $e) {
            return redirect()->route('merchant.organization.reports', ['period' => '7_days'])
                ->withErrors(['date_range' => $e->getMessage()]);
        }

        return view('merchant.organization.reports', [
            'organization' => $organization,
            'branches' => $branches,
            'summary' => $summary,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
