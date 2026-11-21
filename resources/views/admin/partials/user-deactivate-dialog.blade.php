{{--
    Deactivation confirmation for a user account, shared by the user list and the user detail page.
    Expects the parent x-data to hold: deactivateModalOpen, targetUserId, targetUserName, targetUserEmail, targetUserRole.
--}}
@component('admin.partials.dialog', ['open' => 'deactivateModalOpen', 'id' => 'deactivate-user', 'title' => 'Nonaktifkan akun pengguna?'])
    @slot('lead')
        Pengguna tidak dapat masuk sampai akunnya diaktifkan kembali.
    @endslot

    <form method="POST" :action="'{{ url('admin/users') }}/' + targetUserId + '/deactivate'" class="space-y-4">
        @csrf
        <p class="rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700">
            <strong class="font-semibold text-neutral-900" x-text="targetUserName"></strong>
            <span class="block break-all text-neutral-600" x-text="targetUserEmail"></span>
        </p>

        <p x-show="targetUserRole === 'admin'" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-pretty text-amber-900">
            Sistem menolak penonaktifan jika ini satu-satunya administrator yang masih aktif.
        </p>

        <div x-show="targetUserRole === 'merchant'" class="space-y-3">
            <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-pretty text-amber-900">
                Akun UMKM tidak bisa dinonaktifkan selama masih ada tiket yang menunggu, dipanggil, atau sedang dilayani.
            </p>
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-rose-200 bg-rose-50/60 p-3">
                <input type="checkbox" name="emergency_force" value="1" class="mt-0.5 size-5 rounded border-rose-300 text-rose-700 focus:ring-rose-500">
                <span class="text-sm">
                    <span class="block font-semibold text-rose-900">Paksa nonaktifkan (darurat)</span>
                    <span class="mt-0.5 block text-pretty text-rose-800">Semua tiket aktif milik UMKM ini akan dibatalkan otomatis. Tindakan ini tidak dapat dibatalkan.</span>
                </span>
            </label>
        </div>

        <div>
            <label for="deactivation-reason" class="mb-1.5 block text-sm font-medium text-neutral-800">Alasan <span class="text-rose-700">*</span> <span class="font-normal text-neutral-600">(tercatat di log audit)</span></label>
            <x-textarea id="deactivation-reason" name="reason" rows="3" required placeholder="Contoh: Permintaan pemilik akun atau pelanggaran ketentuan." />
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
            <x-button variant="outline" @click="deactivateModalOpen = false">Batal</x-button>
            <x-button type="submit" variant="danger">Nonaktifkan akun</x-button>
        </div>
    </form>
@endcomponent
