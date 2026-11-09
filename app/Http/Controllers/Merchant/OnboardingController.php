<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Display the onboarding wizard.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $business = $user->business;

        // If user already fully onboarded and not requesting a specific step, redirect to dashboard
        if ($user->hasCompletedOnboarding() && ! $request->has('step')) {
            return redirect()->route('merchant.dashboard');
        }

        // Determine active step based on state
        $step = (int) $request->query('step', 0);
        if ($step < 1 || $step > 4) {
            if (! $business) {
                $step = 1;
            } elseif ($business->hours()->count() === 0) {
                $step = 2;
            } elseif ($business->services()->count() === 0) {
                $step = 3;
            } else {
                $step = 4;
            }
        }

        $categories = BusinessCategory::query()->where('is_active', true)->orderBy('name')->get();
        $services = $business ? $business->services()->get() : collect();
        $hours = $business ? $business->hours()->orderBy('day_of_week')->get() : collect();

        $qrSvg = null;
        $publicUrl = null;

        if ($business) {
            $publicUrl = route('customer.queue', ['slug' => $business->slug], true);
            $qrSvg = $this->qrCodeService->generateSvg($publicUrl, 260);
        }

        return view('merchant.onboarding', [
            'step' => $step,
            'business' => $business,
            'categories' => $categories,
            'services' => $services,
            'hours' => $hours,
            'publicUrl' => $publicUrl,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * Handle Step 1: Business Profile submission.
     */
    public function storeStep1(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:business_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'in:Asia/Makassar,Asia/Jakarta,Asia/Jayapura'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $business = $user->business;

            // Generate unique slug
            $baseSlug = Str::slug($validated['name']);
            if (empty($baseSlug)) {
                $baseSlug = 'usaha-'.Str::lower(Str::random(6));
            }

            $slug = $baseSlug;
            $counter = 1;

            while (
                Business::query()
                    ->where('slug', $slug)
                    ->when($business, fn ($q) => $q->where('id', '!=', $business->id))
                    ->exists()
            ) {
                $counter++;
                $slug = "{$baseSlug}-{$counter}";
            }

            if ($business) {
                $business->update([
                    'category_id' => $validated['category_id'],
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                    'timezone' => $validated['timezone'],
                ]);
            } else {
                $business = Business::create([
                    'user_id' => $user->id,
                    'category_id' => $validated['category_id'],
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                    'timezone' => $validated['timezone'],
                    'status' => Business::STATUS_BUKA,
                ]);
            }

            ActivityLog::log(
                action: 'onboarding_step_1',
                description: 'Profil usaha berhasil dikonfigurasi',
                business: $business,
                user: $user
            );
        });

        return redirect()->route('merchant.onboarding', ['step' => 2])
            ->with('status', 'Profil usaha berhasil disimpan! Silakan atur jam operasional.');
    }

    /**
     * Handle Step 2: Weekly Operating Hours submission.
     */
    public function storeStep2(Request $request): RedirectResponse
    {
        $user = $request->user();
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding', ['step' => 1])
                ->withErrors(['general' => 'Harap lengkapi profil usaha terlebih dahulu.']);
        }

        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($user, $business, $validated) {
            foreach ($validated['hours'] as $dayData) {
                $isClosed = ! empty($dayData['is_closed']);

                BusinessHour::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'day_of_week' => $dayData['day_of_week'],
                    ],
                    [
                        'open_time' => $isClosed ? '09:00' : ($dayData['open_time'] ?? '09:00'),
                        'close_time' => $isClosed ? '21:00' : ($dayData['close_time'] ?? '21:00'),
                        'is_closed' => $isClosed,
                    ]
                );
            }

            ActivityLog::log(
                action: 'onboarding_step_2',
                description: 'Jam operasional mingguan berhasil dikonfigurasi',
                business: $business,
                user: $user
            );
        });

        return redirect()->route('merchant.onboarding', ['step' => 3])
            ->with('status', 'Jam operasional berhasil disimpan! Silakan tambahkan layanan perdana.');
    }

    /**
     * Handle Step 3: Initial Service submission.
     */
    public function storeStep3(Request $request): RedirectResponse
    {
        $user = $request->user();
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding', ['step' => 1])
                ->withErrors(['general' => 'Harap lengkapi profil usaha terlebih dahulu.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['required', 'numeric', 'min:0'],
            'daily_quota' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($user, $business, $validated) {
            $service = Service::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'duration_minutes' => $validated['duration_minutes'],
                'price' => $validated['price'],
                'daily_quota' => $validated['daily_quota'] ?? null,
                'is_active' => true,
            ]);

            ActivityLog::log(
                action: 'onboarding_step_3',
                description: sprintf('Layanan perdana "%s" berhasil ditambahkan', $service->name),
                business: $business,
                user: $user
            );
        });

        return redirect()->route('merchant.onboarding', ['step' => 4])
            ->with('status', 'Layanan perdana berhasil ditambahkan! Lihat tautan dan QR Code antrean Anda.');
    }

    /**
     * Complete the onboarding and redirect to merchant dashboard.
     */
    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();
        $business = $user->business;

        if (! $business || ! $user->hasCompletedOnboarding()) {
            return redirect()->route('merchant.onboarding', ['step' => 1])
                ->withErrors(['general' => 'Harap lengkapi seluruh tahapan onboarding terlebih dahulu.']);
        }

        ActivityLog::log(
            action: 'onboarding_completed',
            description: 'Onboarding usaha selesai. Membuka dashboard operasional.',
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.dashboard')
            ->with('status', 'Selamat! Profil usaha Anda telah aktif dan siap menerima antrean.');
    }

    /**
     * Download the business queue QR code as an SVG vector.
     */
    public function downloadQr(Request $request): Response
    {
        $user = $request->user();
        $business = $user->business;

        if (! $business) {
            abort(404, 'Data usaha tidak ditemukan.');
        }

        $publicUrl = route('customer.queue', ['slug' => $business->slug], true);
        $svg = $this->qrCodeService->generateSvg($publicUrl, 512);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => sprintf('attachment; filename="antrein-qr-%s.svg"', $business->slug),
        ]);
    }
}
