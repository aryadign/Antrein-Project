<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    /**
     * Show organization/brand identity settings.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $organization = $user->managedOrganization();

        if (! $organization) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola organisasi.');
        }

        return view('merchant.organization.index', [
            'organization' => $organization,
            'branchesCount' => $organization->branches()->count(),
            'activeBranchesCount' => $organization->branches()->where('is_active', true)->count(),
            'activeBranch' => $user->business,
            'canManage' => true,
        ]);
    }

    /**
     * Update organization/brand identity.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $organization = $user->managedOrganization();

        if (! $organization) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola organisasi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', 'string', 'in:Asia/Makassar,Asia/Jakarta,Asia/Jayapura'],
        ], [
            'name.required' => 'Nama organisasi/brand wajib diisi.',
        ]);

        $organization->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'timezone' => $validated['timezone'],
        ]);

        ActivityLog::log(
            action: 'organization_updated',
            description: sprintf('Profil organisasi "%s" diperbarui', $organization->name),
            business: $user->business,
            user: $user,
            properties: ['organization_id' => $organization->id]
        );

        return redirect()->route('merchant.organization.edit')
            ->with('status', 'Profil organisasi berhasil diperbarui.');
    }
}
