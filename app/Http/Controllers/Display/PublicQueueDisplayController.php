<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Services\DisplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicQueueDisplayController extends Controller
{
    public function __construct(
        protected DisplayService $displayService
    ) {}

    /**
     * Display the public TV/monitor queue screen for a business.
     */
    public function show(Request $request, string $token): View|Response
    {
        $business = $this->displayService->resolveBusiness($token);

        if (! $business) {
            return response()->view('display.not-found', [], 404)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $tokenHash = hash('sha256', $token);
        $displayData = $this->displayService->formatDisplayData($business);

        return response()->view('display.show', [
            'business' => $business,
            'token' => $token,
            'tokenHash' => $tokenHash,
            'displayData' => $displayData,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Read-only JSON endpoint for real-time and polling synchronization.
     */
    public function data(Request $request, string $token): JsonResponse
    {
        $business = $this->displayService->resolveBusiness($token);

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Layar antrean publik tidak aktif atau tautan tidak valid.',
            ], 404)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $displayData = $this->displayService->formatDisplayData($business);

        return response()->json([
            'success' => true,
            'data' => $displayData,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}

