<?php

namespace App\Services;

use App\Events\CounterStatusChanged;
use App\Events\QueueTicketCounterAssigned;
use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
use App\Models\Business;
use App\Models\BusinessCounter;
use App\Models\DailyQueue;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueService
{
    /**
     * Get or atomically initialize a race-safe daily queue record for a business and date.
     * Uses the business's configured timezone to determine the operating date boundary.
     *
     * @param  Business  $business
     * @param  string|null  $date Y-m-d format date string, defaults to current local date
     * @return DailyQueue
     */
    public function getOrCreateDailyQueue(Business $business, ?string $date = null): DailyQueue
    {
        $timezone = $business->timezone ?: 'Asia/Makassar';
        $operatingDate = $date ?? Carbon::now($timezone)->toDateString();

        $dailyQueue = DailyQueue::where('business_id', $business->id)
            ->whereDate('queue_date', $operatingDate)
            ->first();

        if ($dailyQueue) {
            return $dailyQueue;
        }

        try {
            return DailyQueue::create([
                'business_id' => $business->id,
                'queue_date' => $operatingDate,
                'current_number' => 0,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);
        } catch (QueryException $e) {
            // Handle race-condition duplicate key violation on (business_id, queue_date)
            $existing = DailyQueue::where('business_id', $business->id)
                ->whereDate('queue_date', $operatingDate)
                ->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Generate an online customer queue ticket with concurrency-safe numbering and quota enforcement.
     *
     * @param  Business  $business
     * @param  Service  $service
     * @param  string  $customerName
     * @param  string|null  $customerPhone
     * @param  string|null  $idempotencyKey
     * @param  array<int, int>  $activeSessionTicketIds List of ticket IDs held in the customer's browser session
     * @return QueueTicket
     *
     * @throws DomainException
     */
    public function generateOnlineTicket(
        Business $business,
        Service $service,
        string $customerName,
        ?string $customerPhone = null,
        ?string $idempotencyKey = null,
        array $activeSessionTicketIds = [],
        ?string $customerEmail = null,
        ?\DateTimeInterface $customerEmailConsentedAt = null
    ): QueueTicket {
        // 1. Business Operational Validation
        if ($business->is_suspended) {
            throw new DomainException('Pendaftaran antrean tidak dapat dilakukan karena akun usaha sedang ditangguhkan oleh administrator.');
        }

        if ($business->status === Business::STATUS_TUTUP) {
            throw new DomainException('Pendaftaran antrean online tidak dapat dilakukan karena usaha sedang tutup.');
        }

        if ($business->status === Business::STATUS_JEDA) {
            throw new DomainException('Pendaftaran antrean online sedang dijeda sementara oleh pemilik usaha. Silakan coba kembali beberapa saat lagi.');
        }

        // 2. Service Ownership & Status Validation
        if ($service->business_id !== $business->id) {
            throw new DomainException('Layanan yang dipilih tidak terdaftar untuk unit usaha ini.');
        }

        if (! $service->is_active) {
            throw new DomainException('Layanan yang dipilih saat ini sedang tidak aktif.');
        }

        $trimmedName = trim($customerName);
        if ($trimmedName === '') {
            throw new DomainException('Nama pelanggan wajib diisi.');
        }

        // 3. Idempotency Key Check
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = QueueTicket::where('business_id', $business->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                // Verify payload fingerprint: same service and same customer name
                if ($existing->service_id === $service->id && $existing->customer_name === $trimmedName) {
                    return $existing;
                }

                throw new DomainException('Idempotency key telah digunakan dengan data pemesanan yang berbeda.');
            }
        }

        // 4. Session Protection (OQ-10 Policy)
        if (! empty($activeSessionTicketIds)) {
            $activeSessionTickets = QueueTicket::whereIn('id', $activeSessionTicketIds)
                ->active()
                ->get();

            if ($activeSessionTickets->where('service_id', $service->id)->isNotEmpty()) {
                throw new DomainException('Anda sudah memiliki tiket antrean aktif untuk layanan ini pada sesi Anda.');
            }

            if ($activeSessionTickets->count() >= 3) {
                throw new DomainException('Anda telah mencapai batas maksimal 3 tiket antrean aktif secara bersamaan.');
            }
        }

        // 5. Atomic Transaction: Locking, Quota, Numbering & Insert
        return DB::transaction(function () use ($business, $service, $trimmedName, $customerPhone, $idempotencyKey, $customerEmail, $customerEmailConsentedAt) {
            $dailyQueue = $this->getOrCreateDailyQueue($business);

            // Row-level lock on the daily queue record to serialize numbering and quota checks
            /** @var DailyQueue $lockedDailyQueue */
            $lockedDailyQueue = DailyQueue::where('id', $dailyQueue->id)->lockForUpdate()->first();

            // Transactional Quota Check
            $remainingQuota = $service->getRemainingQuotaForDailyQueue($lockedDailyQueue);
            if ($remainingQuota !== null && $remainingQuota <= 0) {
                throw new DomainException('Kuota harian untuk layanan ini telah habis.');
            }

            // Atomic Sequence Increment
            $nextSequence = $lockedDailyQueue->current_number + 1;
            $lockedDailyQueue->current_number = $nextSequence;
            $lockedDailyQueue->save();

            $ticketNumber = sprintf('A-%03d', $nextSequence);
            $accessToken = Str::random(40);

            $ticket = QueueTicket::create([
                'business_id' => $business->id,
                'daily_queue_id' => $lockedDailyQueue->id,
                'service_id' => $service->id,
                'ticket_number' => $ticketNumber,
                'sequence_number' => $nextSequence,
                'customer_name' => $trimmedName,
                'customer_phone' => $customerPhone ? trim($customerPhone) : null,
                'customer_email' => $customerEmail ? trim($customerEmail) : null,
                'customer_email_consented_at' => $customerEmailConsentedAt,
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'access_token' => $accessToken,
                'idempotency_key' => $idempotencyKey ?: null,
            ]);

            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
                'from_status' => null,
                'to_status' => QueueTicket::STATUS_MENUNGGU,
                'actor_type' => QueueStatusHistory::ACTOR_CUSTOMER,
                'note' => 'Pendaftaran antrean online baru',
                'created_at' => now(),
            ]);

            event(new QueueTicketCreated($ticket));

            return $ticket;
        });
    }

    /**
     * Generate a merchant-issued walk-in ticket using the shared FIFO sequence and quota rules.
     *
     * @param  Business  $business
     * @param  Service  $service
     * @param  string  $customerName
     * @param  string|null  $customerPhone
     * @param  string|null  $notes
     * @param  string|null  $idempotencyKey
     * @param  bool  $confirmedPaused Operator explicit confirmation when business status is 'jeda'
     * @param  User|null  $operator
     * @return QueueTicket
     *
     * @throws DomainException
     */
    public function generateWalkInTicket(
        Business $business,
        Service $service,
        string $customerName,
        ?string $customerPhone = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
        bool $confirmedPaused = false,
        ?User $operator = null
    ): QueueTicket {
        if ($business->is_suspended) {
            throw new DomainException('Pendaftaran antrean walk-in tidak dapat dilakukan karena akun usaha sedang disuspend.');
        }

        if ($business->status === Business::STATUS_TUTUP) {
            throw new DomainException('Operasional usaha sedang tutup. Tidak dapat menambahkan antrean walk-in.');
        }

        if ($business->status === Business::STATUS_JEDA && ! $confirmedPaused) {
            throw new DomainException('Operasional usaha sedang berstatus Jeda Sementara. Diperlukan konfirmasi eksplisit dari operator untuk menerbitkan tiket walk-in.');
        }

        if ($service->business_id !== $business->id) {
            throw new DomainException('Layanan yang dipilih tidak terdaftar untuk unit usaha ini.');
        }

        if (! $service->is_active) {
            throw new DomainException('Layanan yang dipilih saat ini sedang tidak aktif.');
        }

        $trimmedName = trim($customerName);
        if ($trimmedName === '') {
            throw new DomainException('Nama pelanggan atau identifikasi walk-in wajib diisi.');
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = QueueTicket::where('business_id', $business->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                if ($existing->service_id === $service->id && $existing->customer_name === $trimmedName) {
                    return $existing;
                }

                throw new DomainException('Idempotency key telah digunakan dengan data pemesanan yang berbeda.');
            }
        }

        return DB::transaction(function () use ($business, $service, $trimmedName, $customerPhone, $notes, $idempotencyKey, $operator) {
            $dailyQueue = $this->getOrCreateDailyQueue($business);

            /** @var DailyQueue $lockedDailyQueue */
            $lockedDailyQueue = DailyQueue::where('id', $dailyQueue->id)->lockForUpdate()->first();

            $remainingQuota = $service->getRemainingQuotaForDailyQueue($lockedDailyQueue);
            if ($remainingQuota !== null && $remainingQuota <= 0) {
                throw new DomainException('Kuota harian untuk layanan ini telah habis.');
            }

            $nextSequence = $lockedDailyQueue->current_number + 1;
            $lockedDailyQueue->current_number = $nextSequence;
            $lockedDailyQueue->save();

            $ticketNumber = sprintf('A-%03d', $nextSequence);
            $accessToken = Str::random(40);

            $ticket = QueueTicket::create([
                'business_id' => $business->id,
                'daily_queue_id' => $lockedDailyQueue->id,
                'service_id' => $service->id,
                'ticket_number' => $ticketNumber,
                'sequence_number' => $nextSequence,
                'customer_name' => $trimmedName,
                'customer_phone' => $customerPhone ? trim($customerPhone) : null,
                'source' => QueueTicket::SOURCE_WALK_IN,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'access_token' => $accessToken,
                'idempotency_key' => $idempotencyKey ?: null,
                'notes' => $notes ? trim($notes) : null,
            ]);

            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
                'from_status' => null,
                'to_status' => QueueTicket::STATUS_MENUNGGU,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator?->id,
                'note' => $notes ?: 'Pendaftaran tiket walk-in di lokasi',
                'created_at' => now(),
            ]);

            event(new QueueTicketCreated($ticket));

            return $ticket;
        });
    }

    /**
     * Call the next waiting ticket according to strict FIFO sequence order.
     * Enforces the Single Active Ticket Per Counter rule (M13):
     * Multiple counters can serve tickets in parallel, but a single counter cannot have multiple active tickets.
     *
     * @param  Business  $business
     * @param  User  $operator
     * @param  BusinessCounter|null  $counter
     * @return QueueTicket
     *
     * @throws DomainException
     */
    public function callNext(Business $business, User $operator, ?BusinessCounter $counter = null): QueueTicket
    {
        // 1. Resolve counter if not explicitly provided
        if ($counter === null) {
            $counter = $operator->assignedCounters()->first();
            if (! $counter) {
                $counter = $business->activeCounters()->first();
            }
        }

        if (! $counter) {
            throw new DomainException('Tidak ada loket aktif yang tersedia untuk memanggil antrean.');
        }

        // 2. Validate counter belongs to this business
        if ($counter->business_id !== $business->id) {
            throw new DomainException('Loket tidak terdaftar untuk unit usaha ini.');
        }

        // 3. Validate counter is active
        if (! $counter->is_active) {
            throw new DomainException(sprintf('Loket %s sedang nonaktif dan tidak dapat memanggil antrean baru.', $counter->name));
        }

        // 4. Validate operator authorization
        if (! $operator->canOperateCounter($counter)) {
            throw new DomainException(sprintf('Anda tidak memiliki hak otorisasi untuk mengoperasikan %s.', $counter->name));
        }

        return DB::transaction(function () use ($business, $operator, $counter) {
            $dailyQueue = $this->getOrCreateDailyQueue($business);

            // Lock the daily queue to serialize operator call actions
            DailyQueue::where('id', $dailyQueue->id)->lockForUpdate()->first();

            // Single Active Ticket Per Counter Rule (M13): Check if this counter currently has an active ticket.
            // Also catches legacy active tickets without an assigned counter (counter_id IS NULL).
            $hasActiveServingTicket = QueueTicket::where('business_id', $business->id)
                ->where(function ($query) use ($counter) {
                    $query->where('counter_id', $counter->id)
                        ->orWhereNull('counter_id');
                })
                ->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])
                ->lockForUpdate()
                ->first();

            if ($hasActiveServingTicket) {
                if ($hasActiveServingTicket->counter_id === null) {
                    $hasActiveServingTicket->counter_id = $counter->id;
                    $hasActiveServingTicket->save();
                }

                throw new DomainException(sprintf(
                    'Tidak dapat memanggil antrean berikutnya. %s masih memiliki antrean yang sedang dipanggil atau sedang dilayani (Nomor %s). Selesaikan, lewati, atau batalkan antrean aktif terlebih dahulu.',
                    $counter->name,
                    $hasActiveServingTicket->ticket_number
                ));
            }

            // Retrieve the oldest waiting ticket by sequence_number from the unified global FIFO
            /** @var QueueTicket|null $nextTicket */
            $nextTicket = QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_MENUNGGU)
                ->orderBy('sequence_number', 'asc')
                ->lockForUpdate()
                ->first();

            if (! $nextTicket) {
                throw new DomainException('Tidak ada tiket antrean yang sedang menunggu.');
            }

            $now = now();
            $nextTicket->update([
                'status' => QueueTicket::STATUS_DIPANGGIL,
                'called_at' => $now,
                'counter_id' => $counter->id,
            ]);

            QueueStatusHistory::create([
                'queue_ticket_id' => $nextTicket->id,
                'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
                'from_status' => QueueTicket::STATUS_MENUNGGU,
                'to_status' => QueueTicket::STATUS_DIPANGGIL,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator->id,
                'note' => sprintf('Memanggil nomor antrean di %s (FIFO)', $counter->name),
                'created_at' => $now,
            ]);

            event(new QueueTicketCounterAssigned($nextTicket, $counter));
            event(new QueueTicketStatusChanged($nextTicket, QueueTicket::STATUS_MENUNGGU));
            event(new CounterStatusChanged($counter));

            return $nextTicket;
        });
    }

    /**
     * Re-announce / recall a ticket that is currently in 'dipanggil' status.
     * Appends an immutable audit event without changing queue sequence or creating new tickets.
     */
    public function recallTicket(QueueTicket $ticket, User $operator, ?BusinessCounter $counter = null): QueueTicket
    {
        if ($ticket->status !== QueueTicket::STATUS_DIPANGGIL) {
            throw new DomainException('Panggilan ulang hanya dapat dilakukan untuk tiket yang sedang berstatus Dipanggil.');
        }

        $ticketCounter = $ticket->counter;
        if ($counter !== null && $ticket->counter_id !== null && $ticket->counter_id !== $counter->id) {
            throw new DomainException('Tiket ini ditugaskan pada loket yang berbeda.');
        }

        $effectiveCounter = $counter ?? $ticketCounter;
        if ($effectiveCounter && ! $operator->canOperateCounter($effectiveCounter)) {
            throw new DomainException('Anda tidak memiliki hak otorisasi untuk mengoperasikan tiket pada loket ini.');
        }

        return DB::transaction(function () use ($ticket, $operator, $effectiveCounter) {
            $now = now();
            $ticket->update(['recalled_at' => $now]);

            $counterNote = $effectiveCounter ? ' di '.$effectiveCounter->name : '';
            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_TICKET_RECALLED,
                'from_status' => QueueTicket::STATUS_DIPANGGIL,
                'to_status' => QueueTicket::STATUS_DIPANGGIL,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator->id,
                'note' => sprintf('Panggilan ulang tiket antrean%s oleh operator', $counterNote),
                'created_at' => $now,
            ]);

            event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_DIPANGGIL));
            if ($effectiveCounter) {
                event(new CounterStatusChanged($effectiveCounter));
            }

            return $ticket;
        });
    }

    /**
     * Transition ticket lifecycle from 'dipanggil' to 'sedang_dilayani'.
     */
    public function startService(QueueTicket $ticket, User $operator, ?BusinessCounter $counter = null): QueueTicket
    {
        if ($ticket->status !== QueueTicket::STATUS_DIPANGGIL) {
            throw new DomainException('Pelayanan hanya dapat dimulai untuk tiket yang sedang berstatus Dipanggil.');
        }

        $ticketCounter = $ticket->counter;
        if ($counter !== null && $ticket->counter_id !== null && $ticket->counter_id !== $counter->id) {
            throw new DomainException('Tiket ini ditugaskan pada loket yang berbeda.');
        }

        $effectiveCounter = $counter ?? $ticketCounter;
        if ($effectiveCounter && ! $operator->canOperateCounter($effectiveCounter)) {
            throw new DomainException('Anda tidak memiliki hak otorisasi untuk mengoperasikan tiket pada loket ini.');
        }

        return DB::transaction(function () use ($ticket, $operator, $effectiveCounter) {
            $now = now();
            $ticket->update([
                'status' => QueueTicket::STATUS_SEDANG_DILAYANI,
                'service_started_at' => $now,
            ]);

            $counterNote = $effectiveCounter ? ' di '.$effectiveCounter->name : '';
            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
                'from_status' => QueueTicket::STATUS_DIPANGGIL,
                'to_status' => QueueTicket::STATUS_SEDANG_DILAYANI,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator->id,
                'note' => sprintf('Memulai proses pelayanan pelanggan%s', $counterNote),
                'created_at' => $now,
            ]);

            event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_DIPANGGIL));
            if ($effectiveCounter) {
                event(new CounterStatusChanged($effectiveCounter));
            }

            return $ticket;
        });
    }

    /**
     * Transition ticket lifecycle from 'sedang_dilayani' to terminal 'selesai'.
     */
    public function completeTicket(QueueTicket $ticket, User $operator, ?string $note = null, ?BusinessCounter $counter = null): QueueTicket
    {
        if ($ticket->status !== QueueTicket::STATUS_SEDANG_DILAYANI) {
            throw new DomainException('Penyelesaian pelayanan hanya dapat dilakukan untuk tiket yang berstatus Sedang Dilayani.');
        }

        $ticketCounter = $ticket->counter;
        if ($counter !== null && $ticket->counter_id !== null && $ticket->counter_id !== $counter->id) {
            throw new DomainException('Tiket ini ditugaskan pada loket yang berbeda.');
        }

        $effectiveCounter = $counter ?? $ticketCounter;
        if ($effectiveCounter && ! $operator->canOperateCounter($effectiveCounter)) {
            throw new DomainException('Anda tidak memiliki hak otorisasi untuk mengoperasikan tiket pada loket ini.');
        }

        return DB::transaction(function () use ($ticket, $operator, $note, $effectiveCounter) {
            $now = now();
            $ticket->update([
                'status' => QueueTicket::STATUS_SELESAI,
                'completed_at' => $now,
            ]);

            $counterNote = $effectiveCounter ? ' di '.$effectiveCounter->name : '';
            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
                'from_status' => QueueTicket::STATUS_SEDANG_DILAYANI,
                'to_status' => QueueTicket::STATUS_SELESAI,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator->id,
                'note' => $note ?: sprintf('Pelayanan pelanggan selesai%s', $counterNote),
                'created_at' => $now,
            ]);

            event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_SEDANG_DILAYANI));
            if ($effectiveCounter) {
                event(new CounterStatusChanged($effectiveCounter));
            }

            return $ticket;
        });
    }

    /**
     * Skip an active ticket from 'menunggu' or 'dipanggil' to terminal 'dilewati'.
     * Skipped tickets continue to consume daily service quota.
     */
    public function skipTicket(QueueTicket $ticket, User $operator, ?string $reason = null, ?BusinessCounter $counter = null): QueueTicket
    {
        if (! in_array($ticket->status, [QueueTicket::STATUS_MENUNGGU, QueueTicket::STATUS_DIPANGGIL], true)) {
            throw new DomainException('Hanya tiket dengan status Menunggu atau Dipanggil yang dapat dilewati.');
        }

        $ticketCounter = $ticket->counter;
        if ($counter !== null && $ticket->counter_id !== null && $ticket->counter_id !== $counter->id) {
            throw new DomainException('Tiket ini ditugaskan pada loket yang berbeda.');
        }

        $effectiveCounter = $counter ?? $ticketCounter;
        if ($effectiveCounter && ! $operator->canOperateCounter($effectiveCounter)) {
            throw new DomainException('Anda tidak memiliki hak otorisasi untuk mengoperasikan tiket pada loket ini.');
        }

        return DB::transaction(function () use ($ticket, $operator, $reason, $effectiveCounter) {
            $previousStatus = $ticket->status;
            $now = now();

            $ticket->update([
                'status' => QueueTicket::STATUS_DILEWATI,
                'skipped_at' => $now,
            ]);

            $counterNote = $effectiveCounter ? ' di '.$effectiveCounter->name : '';
            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_TICKET_SKIPPED,
                'from_status' => $previousStatus,
                'to_status' => QueueTicket::STATUS_DILEWATI,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator->id,
                'note' => $reason ?: sprintf('Nomor antrean dilewati oleh operator%s', $counterNote),
                'created_at' => $now,
            ]);

            event(new QueueTicketStatusChanged($ticket, $previousStatus));
            if ($effectiveCounter) {
                event(new CounterStatusChanged($effectiveCounter));
            }

            return $ticket;
        });
    }

    /**
     * Cancel a waiting ticket from 'menunggu' to terminal 'dibatalkan'.
     * Releases service quota.
     */
    public function cancelTicket(QueueTicket $ticket, User $operator, string $reason): QueueTicket
    {
        if ($ticket->status !== QueueTicket::STATUS_MENUNGGU) {
            throw new DomainException('Hanya tiket dengan status Menunggu yang dapat dibatalkan.');
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new DomainException('Alasan pembatalan tiket wajib diisi.');
        }

        return DB::transaction(function () use ($ticket, $operator, $trimmedReason) {
            $now = now();

            $ticket->update([
                'status' => QueueTicket::STATUS_DIBATALKAN,
                'cancelled_at' => $now,
                'cancellation_reason' => $trimmedReason,
            ]);

            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket->id,
                'event_type' => QueueStatusHistory::EVENT_TICKET_CANCELLED,
                'from_status' => QueueTicket::STATUS_MENUNGGU,
                'to_status' => QueueTicket::STATUS_DIBATALKAN,
                'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
                'actor_id' => $operator->id,
                'note' => $trimmedReason,
                'created_at' => $now,
            ]);

            event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_MENUNGGU));
            if ($ticket->counter) {
                event(new CounterStatusChanged($ticket->counter));
            }

            return $ticket;
        });
    }

    /**
     * Calculate 1-based queue position for a waiting customer ticket.
     * Returns 0 if ticket is not actively waiting.
     */
    public function calculateQueuePosition(QueueTicket $ticket): int
    {
        if ($ticket->status !== QueueTicket::STATUS_MENUNGGU) {
            return 0;
        }

        $aheadCount = QueueTicket::where('daily_queue_id', $ticket->daily_queue_id)
            ->where('status', QueueTicket::STATUS_MENUNGGU)
            ->where('sequence_number', '<', $ticket->sequence_number)
            ->count();

        return $aheadCount + 1;
    }

    /**
     * Get the ticket currently being called or served for today's session.
     * Optionally scoped to a specific counter.
     */
    public function getCurrentServingTicket(Business $business, ?BusinessCounter $counter = null): ?QueueTicket
    {
        $dailyQueue = $this->getOrCreateDailyQueue($business);

        $query = QueueTicket::where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI]);

        if ($counter !== null) {
            $query->where('counter_id', $counter->id);
        }

        return $query->orderBy('called_at', 'desc')->first();
    }

    /**
     * Get all active counters for a business along with their currently active serving ticket.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BusinessCounter>
     */
    public function getActiveCountersStatus(Business $business)
    {
        return $business->activeCounters()
            ->with(['activeTicket.service', 'assignedMemberships.user'])
            ->orderBy('code', 'asc')
            ->get();
    }

    /**
     * Check if a tracking token has expired (terminal tickets expire after 24 hours).
     */
    public function isTokenExpired(QueueTicket $ticket): bool
    {
        if ($ticket->isActive()) {
            return false;
        }

        $terminalTime = $ticket->completed_at
            ?? $ticket->cancelled_at
            ?? $ticket->skipped_at
            ?? $ticket->updated_at;

        if (! $terminalTime) {
            return false;
        }

        return $terminalTime->addHours(24)->isPast();
    }

    /**
     * Get the count of consumed daily quota for a service in a daily queue.
     * Consumed by statuses: menunggu, dipanggil, sedang_dilayani, selesai, dilewati.
     * Released only by: dibatalkan.
     */
    public function getConsumedDailyQuotaCount(Service $service, int $dailyQueueId): int
    {
        return QueueTicket::where('service_id', $service->id)
            ->where('daily_queue_id', $dailyQueueId)
            ->where('status', '!=', QueueTicket::STATUS_DIBATALKAN)
            ->count();
    }
}
