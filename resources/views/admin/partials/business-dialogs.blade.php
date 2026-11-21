{{--
    Reject + suspension confirmation dialogs for UMKM, shared by the dashboard, the UMKM list and the UMKM detail page.
    Expects the parent x-data to hold: rejectModalOpen, suspendModalOpen, targetBusinessId, targetBusinessName, currentSuspended.
--}}
@component('admin.partials.dialog', ['open' => 'rejectModalOpen', 'id' => 'reject-business', 'title' => 'Tolak pendaftaran UMKM'])
    @slot('lead')
        Pemilik akan melihat alasan ini dan dapat memperbaiki data pendaftarannya.
    @endslot

    <form :action="'{{ url('/admin/businesses') }}/' + targetBusinessId + '/reject'" method="POST" class="space-y-4">
        @csrf
        <p class="rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700">UMKM: <strong class="font-semibold text-neutral-900" x-text="targetBusinessName"></strong></p>
        <div>
            <label for="rejection-reason" class="mb-1.5 block text-sm font-medium text-neutral-800">Alasan penolakan <span class="text-rose-700">*</span></label>
            <x-textarea id="rejection-reason" name="rejection_reason" rows="3" required maxlength="500" placeholder="Contoh: Alamat toko kurang lengkap atau nomor telepon belum valid." />
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
            <x-button variant="outline" @click="rejectModalOpen = false">Batal</x-button>
            <x-button type="submit" variant="danger">Tolak pendaftaran</x-button>
        </div>
    </form>
@endcomponent

@component('admin.partials.dialog', ['open' => 'suspendModalOpen', 'id' => 'suspend-business', 'titleExpression' => "currentSuspended ? 'Pulihkan akun UMKM?' : 'Tangguhkan akun UMKM?'"])
    <form :action="'{{ url('/admin/businesses') }}/' + targetBusinessId + '/toggle-suspension'" method="POST" class="space-y-4">
        @csrf
        @method('PATCH')
        <p class="rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700">UMKM: <strong class="font-semibold text-neutral-900" x-text="targetBusinessName"></strong></p>

        <p x-show="!currentSuspended" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-pretty text-rose-800">
            Pelanggan tidak dapat mengambil nomor antrean baru selama akun ditangguhkan. Tiket yang sudah berjalan tetap dapat diselesaikan oleh pemilik.
        </p>
        <p x-show="currentSuspended" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-pretty text-emerald-800">
            UMKM akan kembali tampil dan dapat menerima antrean baru dari pelanggan.
        </p>

        <div>
            <label for="suspension-reason" class="mb-1.5 block text-sm font-medium text-neutral-800">Catatan <span class="font-normal text-neutral-600">(opsional, tercatat di log audit)</span></label>
            <x-textarea id="suspension-reason" name="reason" rows="2" maxlength="500" placeholder="Contoh: Laporan pelanggan berulang tentang layanan." />
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
            <x-button variant="outline" @click="suspendModalOpen = false">Batal</x-button>
            <x-button type="submit" x-bind:class="currentSuspended ? '' : 'bg-danger-700! hover:bg-red-800!'">
                <span x-text="currentSuspended ? 'Ya, pulihkan' : 'Ya, tangguhkan'"></span>
            </x-button>
        </div>
    </form>
@endcomponent
