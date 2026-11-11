<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\MerchantReportService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected MerchantReportService $reportService
    ) {}

    /**
     * Display the merchant queue analytics and performance report page.
     */
    public function index(Request $request): View|RedirectResponse
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
                endDate: $endDate ? (string) $endDate : null
            );
        } catch (DomainException $e) {
            return redirect()->route('merchant.reports', ['period' => '7_days'])
                ->withErrors(['date_range' => $e->getMessage()]);
        }

        return view('merchant.reports.index', [
            'business' => $business,
            'report' => $report,
        ]);
    }
}

