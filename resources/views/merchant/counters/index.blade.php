<x-layouts.merchant title="Kelola Loket Pelayanan — AntreIn">
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-neutral-950">Kelola Loket Pelayanan</h1>
                <p class="text-body text-neutral-600 mt-1">
                    Atur loket operasional, kode pemanggil, dan penugasan operator untuk melayani antrean secara paralel.
                </p>
            </div>
            <div>
                <x-button variant="primary" onclick="document.getElementById('modal-add-counter').showModal()" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Loket
                </x-button>
            </div>
        </div>

        <!-- Success Notifications -->
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p>{{ session('status') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <p class="font-semibold mb-1">Gagal memproses permohonan:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $activeCounters = $counters->where('is_active', true);
            $busyCountersCount = 0;
            foreach ($activeCounters as $c) {
                if ($c->activeTicket()->exists()) {
                    $busyCountersCount++;
                }
            }
            $idleCountersCount = $activeCounters->count() - $busyCountersCount;
        @endphp

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card
                title="Total Loket"
                :value="$counters->count()"
                description="Semua loket terdaftar"
            />
            <x-stat-card
                title="Loket Aktif"
                :value="$activeCounters->count()"
                description="Siap operasional"
            />
            <x-stat-card
                title="Sedang Melayani"
                :value="$busyCountersCount"
                description="Ada tiket aktif"
            />
            <x-stat-card
                title="Loket Tersedia"
                :value="max(0, $idleCountersCount)"
                description="Menunggu pemanggilan"
            />
        </div>

        <!-- Counter List Table -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-neutral-100 flex items-center justify-between">
                <div>
                    <h2 class="text-h3 font-bold text-neutral-900">Daftar Loket Usaha</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Semua loket melayani antrean terpadu (FIFO global) dari antrean harian usaha.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-neutral-50/75 border-b border-neutral-200 text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            <th class="py-3.5 px-4 sm:px-6">Loket</th>
                            <th class="py-3.5 px-4">Kode</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Operator Ditugaskan</th>
                            <th class="py-3.5 px-4">Tiket Aktif</th>
                            <th class="py-3.5 px-4 text-right pr-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($counters as $counter)
                            @php
                                $activeTicket = $counter->activeTicket;
                                $hasActiveTicket = !is_null($activeTicket);
                                $canDeactivate = $counter->is_active && !$hasActiveTicket && $activeCounters->count() > 1;
                            @endphp
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <!-- Counter Info -->
                                <td class="py-4 px-4 sm:px-6 font-medium text-neutral-900">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-xs {{ $counter->is_active ? 'bg-brand-100 text-brand-700' : 'bg-neutral-100 text-neutral-400' }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-neutral-900 flex items-center gap-2">
                                                {{ $counter->name }}
                                                @if (!$counter->is_active)
                                                    <span class="text-[10px] font-medium bg-neutral-100 text-neutral-600 px-1.5 py-0.5 rounded">Nonaktif</span>
                                                @endif
                                            </div>
                                            @if ($counter->description)
                                                <p class="text-xs text-neutral-500 mt-0.5 max-w-xs truncate">{{ $counter->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Code -->
                                <td class="py-4 px-4 font-mono font-bold text-xs text-neutral-700">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-neutral-100 border border-neutral-200">
                                        {{ $counter->code }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4">
                                    @if ($counter->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Operators Assigned -->
                                <td class="py-4 px-4">
                                    <div class="flex flex-wrap items-center gap-1.5 max-w-xs">
                                        @forelse ($counter->assignedMemberships as $membership)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-700 text-xs border border-neutral-200">
                                                <span>{{ $membership->user->name }}</span>
                                                <form action="{{ route('merchant.counters.unassign-operator', [$counter, $membership]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus penugasan staf ini dari loket?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-neutral-400 hover:text-rose-600 ml-0.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </form>
                                            </span>
                                        @empty
                                            <span class="text-xs text-neutral-400 italic">Hanya Pemilik Usaha</span>
                                        @endforelse

                                        <button
                                            type="button"
                                            onclick="openAssignModal({{ $counter->id }}, '{{ addslashes($counter->name) }}')"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:text-brand-800 bg-brand-50 hover:bg-brand-100 px-2 py-0.5 rounded border border-brand-200 transition-colors"
                                        >
                                            + Tugaskan
                                        </button>
                                    </div>
                                </td>

                                <!-- Active Ticket -->
                                <td class="py-4 px-4">
                                    @if ($activeTicket)
                                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-brand-50 border border-brand-200 text-brand-900 text-xs font-semibold">
                                            <span class="font-mono">{{ $activeTicket->formatted_ticket_number }}</span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-normal {{ $activeTicket->status === 'dipanggil' ? 'bg-amber-100 text-amber-800' : 'bg-brand-200 text-brand-800' }}">
                                                {{ $activeTicket->status === 'dipanggil' ? 'Dipanggil' : 'Melayani' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-neutral-400">Kosong / Siap</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-4 pr-6 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <!-- Edit button -->
                                        <button
                                            type="button"
                                            onclick="openEditModal({{ $counter->id }}, '{{ addslashes($counter->name) }}', '{{ addslashes($counter->code) }}', '{{ addslashes($counter->description ?? '') }}')"
                                            class="text-xs font-medium text-neutral-600 hover:text-brand-600 px-2 py-1 rounded hover:bg-neutral-100 transition-colors"
                                        >
                                            Edit
                                        </button>

                                        <!-- Toggle Status -->
                                        <form action="{{ route('merchant.counters.toggle-status', $counter) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            @if ($counter->is_active)
                                                <button
                                                    type="submit"
                                                    @if (!$canDeactivate)
                                                        disabled
                                                        title="{{ $hasActiveTicket ? 'Tidak dapat dinonaktifkan: ada tiket aktif.' : 'Tidak dapat dinonaktifkan: minimal satu loket aktif diperlukan.' }}"
                                                    @endif
                                                    class="text-xs font-medium px-2 py-1 rounded transition-colors {{ $canDeactivate ? 'text-amber-600 hover:text-amber-800 hover:bg-amber-50' : 'text-neutral-300 cursor-not-allowed' }}"
                                                    onclick="return confirm('Nonaktifkan {{ $counter->name }}? Loket tidak akan menerima antrean baru.')"
                                                >
                                                    Nonaktifkan
                                                </button>
                                            @else
                                                <button
                                                    type="submit"
                                                    class="text-xs font-medium text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 px-2 py-1 rounded transition-colors"
                                                >
                                                    Aktifkan
                                                </button>
                                            @endif
                                        </form>

                                        <!-- Delete -->
                                        @if (!$hasActiveTicket && $counters->count() > 1)
                                            <form action="{{ route('merchant.counters.destroy', $counter) }}" method="POST" class="inline" onsubmit="return confirm('Hapus {{ $counter->name }} secara permanen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800 px-2 py-1 rounded hover:bg-rose-50 transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-neutral-500">
                                    Belum ada loket yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Loket -->
    <dialog id="modal-add-counter" class="rounded-2xl shadow-xl backdrop:bg-neutral-950/50 p-0 w-full max-w-md">
        <form method="POST" action="{{ route('merchant.counters.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-h3 font-bold text-neutral-900">Tambah Loket Pelayanan</h3>
                <button type="button" onclick="document.getElementById('modal-add-counter').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div>
                <label for="add_name" class="block text-xs font-semibold text-neutral-700 mb-1">Nama Loket *</label>
                <input id="add_name" name="name" type="text" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Contoh: Loket 2" required />
            </div>

            <div>
                <label for="add_code" class="block text-xs font-semibold text-neutral-700 mb-1">Kode Loket *</label>
                <input id="add_code" name="code" type="text" class="w-full rounded-lg border-neutral-300 text-sm font-mono uppercase focus:border-brand-500 focus:ring-brand-500" placeholder="Contoh: L-02" required maxlength="10" />
                <p class="text-[11px] text-neutral-500 mt-1">Kode unik identifikasi loket, maksimal 10 karakter.</p>
            </div>

            <div>
                <label for="add_description" class="block text-xs font-semibold text-neutral-700 mb-1">Deskripsi (Opsional)</label>
                <textarea id="add_description" name="description" rows="2" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Contoh: Loket khusus pembayaran & konsultasi cepat"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                <x-button type="button" variant="secondary" onclick="document.getElementById('modal-add-counter').close()">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan Loket</x-button>
            </div>
        </form>
    </dialog>

    <!-- Modal Edit Loket -->
    <dialog id="modal-edit-counter" class="rounded-2xl shadow-xl backdrop:bg-neutral-950/50 p-0 w-full max-w-md">
        <form method="POST" id="form-edit-counter" action="" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-h3 font-bold text-neutral-900">Edit Loket Pelayanan</h3>
                <button type="button" onclick="document.getElementById('modal-edit-counter').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div>
                <label for="edit_name" class="block text-xs font-semibold text-neutral-700 mb-1">Nama Loket *</label>
                <input id="edit_name" name="name" type="text" class="w-full rounded-lg border-neutral-300 text-sm focus:border-brand-500 focus:ring-brand-500" required />
            </div>

            <div>
                <label for="edit_code" class="block text-xs font-semibold text-neutral-700 mb-1">Kode Loket *</label>
                <input id="edit_code" name="code" type="text" class="w-full rounded-lg border-neutral-300 text-sm font-mono uppercase focus:border-brand-500 focus:ring-brand-500" required maxlength="10" />
            </div>

            <div>
                <label for="edit_description" class="block text-xs font-semibold text-neutral-700 mb-1">Deskripsi (Opsional)</label>
                <textarea id="edit_description" name="description" rows="2" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                <x-button type="button" variant="secondary" onclick="document.getElementById('modal-edit-counter').close()">Batal</x-button>
                <x-button type="submit" variant="primary">Perbarui Loket</x-button>
            </div>
        </form>
    </dialog>

    <!-- Modal Tugaskan Operator -->
    <dialog id="modal-assign-operator" class="rounded-2xl shadow-xl backdrop:bg-neutral-950/50 p-0 w-full max-w-md">
        <form method="POST" id="form-assign-operator" action="" class="p-6 space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-h3 font-bold text-neutral-900">Tugaskan Operator</h3>
                    <p class="text-xs text-neutral-500 mt-0.5" id="assign-counter-title">Pilih staf untuk loket ini</p>
                </div>
                <button type="button" onclick="document.getElementById('modal-assign-operator').close()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div>
                <label for="assign_membership_id" class="block text-xs font-semibold text-neutral-700 mb-1">Pilih Staf Operator *</label>
                <select id="assign_membership_id" name="business_membership_id" class="mt-1 block w-full rounded-lg border-neutral-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
                    <option value="">-- Pilih Anggota Staf --</option>
                    @foreach ($availableOperators as $op)
                        <option value="{{ $op->id }}">{{ $op->user->name }} ({{ $op->user->email }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-neutral-500 mt-1">Staf operator yang ditugaskan dapat memilih dan mengoperasikan loket ini dari dashboard.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                <x-button type="button" variant="secondary" onclick="document.getElementById('modal-assign-operator').close()">Batal</x-button>
                <x-button type="submit" variant="primary">Tugaskan</x-button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditModal(counterId, name, code, description) {
            const form = document.getElementById('form-edit-counter');
            form.action = `/umkm/counters/${counterId}`;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_description').value = description;
            document.getElementById('modal-edit-counter').showModal();
        }

        function openAssignModal(counterId, counterName) {
            const form = document.getElementById('form-assign-operator');
            form.action = `/umkm/counters/${counterId}/operators`;
            document.getElementById('assign-counter-title').textContent = `Tugaskan staf untuk ${counterName}`;
            document.getElementById('modal-assign-operator').showModal();
        }
    </script>
</x-layouts.merchant>
