<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Opening hours applied when an owner finishes onboarding without configuring step 2:
     * Monday to Saturday 09:00–21:00, Sunday closed.
     *
     * @var array{open_time: string, close_time: string, closed_days: list<int>}
     */
    public const array DEFAULT_HOURS = [
        'open_time' => '09:00',
        'close_time' => '21:00',
        'closed_days' => [0],
    ];

    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Display the onboarding wizard.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Operators belong to an existing business and never need to run owner onboarding
        if ($user->isOperator()) {
            return redirect()->route('merchant.dashboard');
        }

        $business = $user->business;

        // If user already fully onboarded and not requesting a specific step, redirect appropriately
        if ($user->hasCompletedOnboarding() && ! $request->has('step')) {
            if ($business && ! $business->isApproved()) {
                return redirect()->route('merchant.approval.status');
            }

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
            'areas' => Business::AREAS,
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

        if ($user->isOperator()) {
            abort(403, 'Akses terbatas untuk Pemilik Usaha.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:business_categories,id'],
            'area' => ['nullable', 'string', Rule::in(array_keys(Business::AREAS))],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'in:Asia/Makassar,Asia/Jakarta,Asia/Jayapura'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'logo.image' => 'Logo usaha harus berupa gambar.',
            'logo.mimes' => 'Logo usaha harus berformat JPG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo usaha maksimal 2 MB.',
        ]);

        $oldLogo = $user->business?->logo_path;
        $newLogoPath = $request->hasFile('logo')
            ? $request->file('logo')->storeAs('logos', 'logo-'.Str::random(32).'.'.$request->file('logo')->extension(), 'public')
            : null;

        try {
            DB::transaction(function () use ($user, $validated, $newLogoPath) {
                $this->saveBusinessProfile($user, $validated, $newLogoPath);
            });
        } catch (\Throwable $exception) {
            if ($newLogoPath !== null) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($newLogoPath !== null && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('merchant.onboarding', ['step' => 2])
            ->with('status', 'Profil usaha berhasil disimpan! Silakan atur jam operasional.');
    }

    /**
     * Create or update the owner's business from the step 1 profile data.
     *
     * @param  array{name: string, category_id: int, area?: ?string, description?: ?string, address: string, phone: string, timezone: string}  $validated
     */
    protected function saveBusinessProfile(User $user, array $validated, ?string $logoPath): void
    {
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

        $profile = [
            'category_id' => $validated['category_id'],
            'area' => $validated['area'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'timezone' => $validated['timezone'],
        ];

        if ($logoPath !== null) {
            $profile['logo_path'] = $logoPath;
        }

        if ($business) {
            $business->update($profile);
        } else {
            $business = Business::create([
                'user_id' => $user->id,
                ...$profile,
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_PENDING,
            ]);
        }

        $user->forgetActiveBusiness();

        ActivityLog::log(
            action: 'onboarding_step_1',
            description: 'Profil usaha berhasil dikonfigurasi',
            business: $business,
            user: $user
        );
    }

    /**
     * Handle Step 2: Weekly Operating Hours submission.
     */
    public function storeStep2(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isOperator()) {
            abort(403, 'Akses terbatas untuk Pemilik Usaha.');
        }

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

        if ($user->isOperator()) {
            abort(403, 'Akses terbatas untuk Pemilik Usaha.');
        }

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
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'image.image' => 'Foto layanan harus berupa gambar.',
            'image.mimes' => 'Foto layanan harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran foto layanan maksimal 2 MB.',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('services/'.$business->id, 'public')
            : null;

        try {
            DB::transaction(function () use ($user, $business, $validated, $imagePath) {
                $service = Service::create([
                    'business_id' => $business->id,
                    'name' => $validated['name'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'price' => $validated['price'],
                    'daily_quota' => $validated['daily_quota'] ?? null,
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);

                ActivityLog::log(
                    action: 'onboarding_step_3',
                    description: sprintf('Layanan perdana "%s" berhasil ditambahkan', $service->name),
                    business: $business,
                    user: $user
                );
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()->route('merchant.onboarding', ['step' => 4])
            ->with('status', 'Layanan perdana berhasil ditambahkan! Lihat QR Code antrean Anda.');
    }

    /**
     * Complete the onboarding and redirect to the waiting approval page (or dashboard if approved).
     */
    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isOperator()) {
            abort(403, 'Akses terbatas untuk Pemilik Usaha.');
        }

        $business = $user->business;

        if (! $business || ! $user->hasCompletedOnboarding()) {
            return redirect()->route('merchant.onboarding', ['step' => 1])
                ->withErrors(['general' => 'Harap lengkapi seluruh tahapan onboarding terlebih dahulu.']);
        }

        if ($business->hours()->count() === 0) {
            $this->applyDefaultHours($business);
        }

        ActivityLog::log(
            action: 'onboarding_completed',
            description: 'Onboarding usaha selesai. Menunggu verifikasi administrator AntreIn.',
            business: $business,
            user: $user
        );

        if (! $business->isApproved()) {
            return redirect()->route('merchant.approval.status')
                ->with('status', 'Pendaftaran UMKM Anda telah dikirim dan sedang dalam proses peninjauan oleh Admin AntreIn.');
        }

        return redirect()->route('merchant.dashboard')
            ->with('status', 'Selamat! Profil usaha Anda telah aktif dan siap menerima antrean.');
    }

    /**
     * Display the registration waiting/rejection status page for UMKM owners.
     */
    public function approvalStatus(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $business = $user->business;

        if (! $business || ! $user->hasCompletedOnboarding()) {
            return redirect()->route('merchant.onboarding');
        }

        if ($business->isApproved()) {
            return redirect()->route('merchant.dashboard');
        }

        $business->load(['category', 'services', 'hours']);

        return view('merchant.approval-status', [
            'business' => $business,
        ]);
    }

    /**
     * Resubmit a rejected UMKM registration for admin review.
     */
    public function resubmit(Request $request): RedirectResponse
    {
        $user = $request->user();
        $business = $user->business;

        if (! $business || $user->isOperator()) {
            abort(403, 'Akses terbatas untuk Pemilik Usaha.');
        }

        if ($business->isRejected()) {
            $business->update([
                'approval_status' => Business::APPROVAL_PENDING,
                'rejection_reason' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);

            ActivityLog::log(
                action: 'business_resubmitted',
                description: sprintf('Pemilik usaha mengajukan ulang pendaftaran UMKM "%s" untuk ditinjau admin.', $business->name),
                business: $business,
                user: $user
            );
        }

        return redirect()->route('merchant.approval.status')
            ->with('status', 'Pengajuan ulang UMKM Anda telah dikirim dan sedang dalam proses verifikasi admin.');
    }

    /**
     * Give a business that skipped step 2 a sensible weekly schedule (see DEFAULT_HOURS).
     */
    protected function applyDefaultHours(Business $business): void
    {
        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::create([
                'business_id' => $business->id,
                'day_of_week' => $dayOfWeek,
                'open_time' => self::DEFAULT_HOURS['open_time'],
                'close_time' => self::DEFAULT_HOURS['close_time'],
                'is_closed' => in_array($dayOfWeek, self::DEFAULT_HOURS['closed_days'], true),
            ]);
        }
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
