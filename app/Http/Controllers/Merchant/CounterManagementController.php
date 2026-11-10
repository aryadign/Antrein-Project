<?php

namespace App\Http\Controllers\Merchant;

use App\Events\CounterStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CounterManagementController extends Controller
{
    /**
     * Display the list of business counters with assignments and active operational status.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $counters = $business->counters()
            ->with(['assignedMemberships.user', 'activeTicket.service'])
            ->orderBy('code', 'asc')
            ->get();

        $operators = $business->memberships()
            ->where('is_active', true)
            ->where('role', BusinessMembership::ROLE_OPERATOR)
            ->with('user')
            ->get();

        $totalCounters = $counters->count();
        $activeCountersCount = $counters->where('is_active', true)->count();
        $busyCountersCount = $counters->filter(fn ($c) => $c->hasActiveTicket())->count();
        $availableCountersCount = max(0, $activeCountersCount - $busyCountersCount);

        return view('merchant.counters.index', [
            'business' => $business,
            'counters' => $counters,
            'operators' => $operators,
            'availableOperators' => $operators,
            'totalCounters' => $totalCounters,
            'activeCountersCount' => $activeCountersCount,
            'busyCountersCount' => $busyCountersCount,
            'availableCountersCount' => $availableCountersCount,
        ]);
    }

    /**
     * Store a newly created counter for this business.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('business_counters')->where('business_id', $business->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'operator_membership_ids' => ['nullable', 'array'],
            'operator_membership_ids.*' => ['integer', 'exists:business_memberships,id'],
        ], [
            'name.required' => 'Nama loket wajib diisi.',
            'code.required' => 'Kode loket wajib diisi.',
            'code.unique' => 'Kode loket sudah digunakan pada unit usaha ini.',
        ]);

        /** @var BusinessCounter $counter */
        $counter = $business->counters()->create([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active' => true,
        ]);

        if (! empty($validated['operator_membership_ids'])) {
            $validMemberships = $business->memberships()
                ->whereIn('id', $validated['operator_membership_ids'])
                ->pluck('id');

            $counter->assignedMemberships()->sync($validMemberships);
        }

        ActivityLog::log(
            action: 'counter_created',
            description: sprintf('Loket baru %s (%s) dibuat', $counter->name, $counter->code),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.counters.index')
            ->with('status', sprintf('Loket %s (%s) berhasil ditambahkan.', $counter->name, $counter->code));
    }

    /**
     * Update counter details.
     */
    public function update(Request $request, BusinessCounter $counter): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($counter->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola loket ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('business_counters')
                    ->where('business_id', $business->id)
                    ->ignore($counter->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama loket wajib diisi.',
            'code.required' => 'Kode loket wajib diisi.',
            'code.unique' => 'Kode loket sudah digunakan pada unit usaha ini.',
        ]);

        $counter->update([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
        ]);

        ActivityLog::log(
            action: 'counter_updated',
            description: sprintf('Data loket %s (%s) diperbarui', $counter->name, $counter->code),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.counters.index')
            ->with('status', sprintf('Data loket %s berhasil diperbarui.', $counter->name));
    }

    /**
     * Toggle the active/inactive status of a counter.
     */
    public function toggleStatus(Request $request, BusinessCounter $counter): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($counter->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola loket ini.');
        }

        // Deactivation guard: Check if counter has active serving/called ticket
        if ($counter->is_active) {
            if ($counter->hasActiveTicket()) {
                return back()->withErrors(['counter_error' => sprintf(
                    'Loket %s tidak dapat dinonaktifkan karena saat ini masih melayani antrean aktif.',
                    $counter->name
                )]);
            }

            // Guard: Ensure business maintains at least one active counter
            $activeCount = $business->activeCounters()->count();
            if ($activeCount <= 1) {
                return back()->withErrors(['counter_error' => 'Unit usaha harus memiliki minimal satu loket yang aktif beroperasi.']);
            }
        }

        $newStatus = ! $counter->is_active;
        $counter->update(['is_active' => $newStatus]);

        event(new CounterStatusChanged($counter));

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLog::log(
            action: $newStatus ? 'counter_activated' : 'counter_deactivated',
            description: sprintf('Status loket %s (%s) %s', $counter->name, $counter->code, $statusLabel),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.counters.index')
            ->with('status', sprintf('Loket %s berhasil %s.', $counter->name, $statusLabel));
    }

    /**
     * Delete a counter.
     */
    public function destroy(Request $request, BusinessCounter $counter): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($counter->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus loket ini.');
        }

        // Active ticket guard
        if ($counter->hasActiveTicket()) {
            return back()->withErrors(['counter_error' => sprintf(
                'Loket %s tidak dapat dihapus karena masih memiliki antrean aktif.',
                $counter->name
            )]);
        }

        // Minimum counter guard
        if ($business->counters()->count() <= 1) {
            return back()->withErrors(['counter_error' => 'Unit usaha tidak boleh menghapus satu-satunya loket yang tersedia.']);
        }

        // Historical reference guard: cannot delete if tickets exist
        if ($counter->tickets()->exists()) {
            return back()->withErrors(['counter_error' => sprintf(
                'Loket %s telah memiliki riwayat antrean pelanggan dan tidak dapat dihapus. Silakan nonaktifkan loket tersebut.',
                $counter->name
            )]);
        }

        $counterName = $counter->name;
        $counterCode = $counter->code;

        $counter->delete();

        ActivityLog::log(
            action: 'counter_deleted',
            description: sprintf('Loket %s (%s) dihapus', $counterName, $counterCode),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.counters.index')
            ->with('status', sprintf('Loket %s (%s) berhasil dihapus.', $counterName, $counterCode));
    }

    /**
     * Assign an operator to a counter.
     */
    public function assignOperator(Request $request, BusinessCounter $counter): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($counter->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola loket ini.');
        }

        $validated = $request->validate([
            'business_membership_id' => ['required', 'integer', 'exists:business_memberships,id'],
        ]);

        /** @var BusinessMembership $membership */
        $membership = $business->memberships()
            ->where('id', $validated['business_membership_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $counter->assignedMemberships()->syncWithoutDetaching([$membership->id]);

        ActivityLog::log(
            action: 'counter_operator_assigned',
            description: sprintf('Staf %s ditugaskan ke loket %s (%s)', $membership->user->name, $counter->name, $counter->code),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.counters.index')
            ->with('status', sprintf('Staf %s berhasil ditugaskan ke %s.', $membership->user->name, $counter->name));
    }

    /**
     * Unassign an operator from a counter.
     */
    public function unassignOperator(
        Request $request,
        BusinessCounter $counter,
        BusinessMembership $membership
    ): RedirectResponse {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($counter->business_id !== $business->id || $membership->business_id !== $business->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $counter->assignedMemberships()->detach($membership->id);

        ActivityLog::log(
            action: 'counter_operator_unassigned',
            description: sprintf('Penugasan staf %s dari loket %s dicabut', $membership->user->name, $counter->name),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.counters.index')
            ->with('status', sprintf('Penugasan staf %s dari %s berhasil dicabut.', $membership->user->name, $counter->name));
    }
}
