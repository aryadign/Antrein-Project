<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerReviewController extends Controller
{
    /**
     * Display customer reviews and feedback analytics for the merchant's business.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        // Metrics calculation (strictly tenant-isolated)
        $totalReviews = $business->customerReviews()->count();
        $averageRating = $business->averageRating();

        $completedTicketsCount = $business->queueTickets()
            ->where('status', QueueTicket::STATUS_SELESAI)
            ->count();

        $reviewRate = $completedTicketsCount > 0
            ? round(($totalReviews / $completedTicketsCount) * 100, 1)
            : 0;

        $ratingsDistribution = [
            5 => $business->customerReviews()->where('rating', 5)->count(),
            4 => $business->customerReviews()->where('rating', 4)->count(),
            3 => $business->customerReviews()->where('rating', 3)->count(),
            2 => $business->customerReviews()->where('rating', 2)->count(),
            1 => $business->customerReviews()->where('rating', 1)->count(),
        ];

        $ratingBreakdown = [];
        foreach ($ratingsDistribution as $star => $count) {
            $ratingBreakdown[$star] = [
                'count' => $count,
                'percent' => $totalReviews > 0 ? (int) round(($count / $totalReviews) * 100) : 0,
            ];
        }

        $reviewsWithCommentCount = $business->customerReviews()
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->count();

        $withCommentOnly = $request->boolean('dengan_komentar');
        $reviewSort = (string) $request->query('urut_ulasan', 'terbaru');
        if (! in_array($reviewSort, ['terbaru', 'rating_tinggi', 'rating_rendah'], true)) {
            $reviewSort = 'terbaru';
        }

        // Query with filters
        $query = $business->customerReviews()
            ->with(['queueTicket', 'service']);

        if ($request->filled('rating') && in_array((int) $request->rating, [1, 2, 3, 4, 5], true)) {
            $query->where('rating', (int) $request->rating);
        }

        if ($withCommentOnly) {
            $query->whereNotNull('comment')->where('comment', '!=', '');
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('search')) {
            $searchTerm = trim($request->string('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('comment', 'like', "%{$searchTerm}%")
                    ->orWhereHas('queueTicket', function ($ticketQuery) use ($searchTerm) {
                        $ticketQuery->where('ticket_number', 'like', "%{$searchTerm}%")
                            ->orWhere('customer_name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        match ($reviewSort) {
            'rating_tinggi' => $query->orderByDesc('rating')->latest(),
            'rating_rendah' => $query->orderBy('rating')->latest(),
            default => $query->latest(),
        };

        $reviews = $query->paginate(15)->withQueryString();
        $services = $business->services()->orderBy('name')->get();

        return view('merchant.reviews.index', [
            'business' => $business,
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'completedTicketsCount' => $completedTicketsCount,
            'reviewRate' => $reviewRate,
            'ratingsDistribution' => $ratingsDistribution,
            'ratingBreakdown' => $ratingBreakdown,
            'reviewsWithCommentCount' => $reviewsWithCommentCount,
            'withCommentOnly' => $withCommentOnly,
            'reviewSort' => $reviewSort,
            'services' => $services,
        ]);
    }
}
