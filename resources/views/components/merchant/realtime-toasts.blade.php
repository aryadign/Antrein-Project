@props([
    'businessId',
    'counterId' => null,
])

{{-- Realtime queue notifications (Laravel Echo). Toasts sit above the mobile thumb-zone dock. --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (! window.Echo) {
            return;
        }

        const businessId = @js($businessId);
        const counterId = @js($counterId);

        window.Echo.private('business.' + businessId)
            .listen('.QueueTicketCreated', (e) => showRealtimeToast('Tiket Masuk', 'Nomor ' + e.ticket_number + ' (' + e.customer_name + ') baru terdaftar.'))
            .listen('.QueueTicketStatusChanged', (e) => showRealtimeToast('Status Antrean', 'Tiket ' + e.ticket_number + ' kini ' + e.status + (e.counter_name ? ' di ' + e.counter_name : '') + '.'))
            .listen('.QueueTicketCounterAssigned', (e) => showRealtimeToast('Penugasan Loket', 'Tiket ' + e.ticket_number + ' dipanggil ke ' + e.counter_name + '.'))
            .listen('.CounterStatusChanged', (e) => showRealtimeToast('Status Loket', 'Loket ' + e.counter_name + (e.is_active ? ' aktif' : ' nonaktif') + '.'));

        if (counterId) {
            window.Echo.private('counter.' + counterId)
                .listen('.QueueTicketCounterAssigned', (e) => showRealtimeToast('Loket Anda', 'Tiket ' + e.ticket_number + ' dialihkan ke loket ini.'));
        }

        function showRealtimeToast(title, message) {
            let container = document.getElementById('merchant-realtime-toast-container');
            if (! container) {
                container = document.createElement('div');
                container.id = 'merchant-realtime-toast-container';
                container.className = 'fixed inset-x-4 bottom-40 z-40 flex flex-col gap-2 pointer-events-none sm:inset-x-auto sm:bottom-5 sm:right-5 sm:max-w-sm';
                container.setAttribute('aria-live', 'polite');
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-neutral-900 text-white text-xs font-medium px-4 py-3 rounded-xl shadow-xl border border-neutral-700 flex items-start justify-between gap-3';

            const body = document.createElement('div');
            body.className = 'flex items-start gap-2.5';
            body.innerHTML = '<svg class="w-4 h-4 mt-0.5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';

            const text = document.createElement('div');
            const titleEl = document.createElement('div');
            titleEl.className = 'font-bold text-neutral-200';
            titleEl.textContent = title;
            const messageEl = document.createElement('div');
            messageEl.className = 'text-neutral-400 mt-0.5';
            messageEl.textContent = message;
            text.append(titleEl, messageEl);
            body.appendChild(text);

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'text-neutral-400 hover:text-white shrink-0 -m-2 p-2';
            close.setAttribute('aria-label', 'Tutup notifikasi');
            close.innerHTML = '&times;';
            close.addEventListener('click', () => toast.remove());

            toast.append(body, close);
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 6000);
        }
    });
</script>
@endpush
