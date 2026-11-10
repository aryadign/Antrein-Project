<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CustomerReview;
use App\Models\QueueTicket;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {}

    /**
     * Submit a customer review and feedback for a completed queue ticket.
     */
    public function store(Request $request, string $token): JsonResponse|RedirectResponse
    {
        /** @var QueueTicket $ticket */
        $ticket = QueueTicket::where('access_token', $token)
            ->with(['business', 'service'])
            ->firstOrFail();

        // 1. Check if token is expired
        if ($this->queueService->isTokenExpired($ticket)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tiket antrean ini telah kedaluwarsa sehingga ulasan tidak dapat diberikan.',
                ], 410);
            }

            return back()->withErrors(['review' => 'Tiket antrean ini telah kedaluwarsa sehingga ulasan tidak dapat diberikan.']);
        }

        // 2. Lifecycle check: Only completed tickets can be reviewed
        if ($ticket->status !== QueueTicket::STATUS_SELESAI) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ulasan hanya dapat diberikan setelah pelayanan antrean selesai.',
                ], 422);
            }

            return back()->withErrors(['review' => 'Ulasan hanya dapat diberikan setelah pelayanan antrean selesai.']);
        }

        // 3. Idempotency / Duplicate protection: strictly 1 review per ticket
        if ($ticket->review()->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ulasan untuk tiket antrean ini sudah pernah dikirimkan.',
                ], 422);
            }

            return back()->withErrors(['review' => 'Ulasan untuk tiket antrean ini sudah pernah dikirimkan.']);
        }

        // 4. Validate input
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Penilaian bintang wajib dipilih (1-5 bintang).',
            'rating.between' => 'Penilaian bintang harus antara 1 sampai 5.',
            'comment.max' => 'Ulasan maksimal 1000 karakter.',
        ]);

        // 5. Anti-XSS and whitespace sanitization
        $cleanComment = ! empty($validated['comment'])
            ? strip_tags(trim($validated['comment']))
            : null;

        // 6. Persist review
        $review = CustomerReview::create([
            'business_id' => $ticket->business_id,
            'queue_ticket_id' => $ticket->id,
            'service_id' => $ticket->service_id,
            'rating' => (int) $validated['rating'],
            'comment' => $cleanComment,
        ]);

        // 7. Audit logging
        ActivityLog::log(
            action: 'customer_review_submitted',
            description: sprintf('Pelanggan tiket %s memberikan ulasan rating %d bintang', $ticket->ticket_number, $review->rating),
            business: $ticket->business
        );

        $message = 'Terima kasih atas ulasan dan masukan Anda!';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->toIso8601String(),
                ],
            ], 201);
        }

        return redirect()->route('customer.queue.track', ['token' => $ticket->access_token])
            ->with('status', $message);
    }
}

