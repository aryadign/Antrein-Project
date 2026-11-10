<?php

namespace App\Console\Commands;

use App\Models\QueueTicket;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnonymizeQueuePiiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queues:anonymize-pii
                            {--dry-run : Hitung tiket yang memenuhi kriteria tanpa mengubah database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymize customer PII on terminal queue tickets past the ADR-13 retention window';

    /**
     * Number of tickets processed per database transaction chunk.
     */
    protected int $chunkSize = 200;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = $this->resolveRetentionDays();

        if ($retentionDays === null) {
            return self::FAILURE;
        }

        $cutoff = now()->subDays($retentionDays);
        $eligible = $this->eligibleTicketsQuery($cutoff)->count();

        if ($this->option('dry-run')) {
            $this->info("[DRY-RUN] {$eligible} tiket terminal berumur melebihi {$retentionDays} hari memenuhi kriteria anonimisasi PII. Tidak ada data yang diubah.");

            return self::SUCCESS;
        }

        if ($eligible === 0) {
            $this->info('Tidak ada tiket yang memenuhi kriteria anonimisasi PII.');

            return self::SUCCESS;
        }

        $processed = 0;
        $failure = null;

        $this->eligibleTicketsQuery($cutoff)->chunkById($this->chunkSize, function ($tickets) use (&$processed, &$failure): bool {
            try {
                DB::transaction(function () use ($tickets, &$processed): void {
                    foreach ($tickets as $ticket) {
                        $ticket->fill($this->anonymizedAttributes($ticket))->saveQuietly();
                        $processed++;
                    }
                });
            } catch (Throwable $exception) {
                $failure = $exception;

                return false;
            }

            return true;
        });

        if ($failure !== null) {
            // Only anonymized values are ever written by this command, so the
            // exception context cannot disclose customer PII (ADR-13).
            Log::error('PII anonymization failed after processing '.$processed.' tickets.', [
                'exception' => $failure->getMessage(),
            ]);

            $this->error('Anonimisasi PII gagal setelah memproses '.$processed.' tiket. Silakan periksa log aplikasi untuk detail kesalahan.');

            return self::FAILURE;
        }

        Log::info('PII anonymization completed.', [
            'retention_days' => $retentionDays,
            'processed' => $processed,
        ]);

        $this->info("{$processed} tiket berhasil dianonimisasi (retensi {$retentionDays} hari).");

        return self::SUCCESS;
    }

    /**
     * Resolve and validate the configured retention window in days.
     */
    protected function resolveRetentionDays(): ?int
    {
        $days = (int) config('app.queue_pii_retention_days', 30);

        if ($days < 1) {
            $this->error('Konfigurasi QUEUE_PII_RETENTION_DAYS harus bernilai minimal 1 (terbaca: '.$days.').');

            return null;
        }

        return $days;
    }

    /**
     * Terminal tickets past the retention window that still hold unmasked
     * customer PII (ADR-13). Active tickets are never eligible.
     *
     * @param  CarbonInterface  $cutoff
     * @return \Illuminate\Database\Eloquent\Builder<QueueTicket>
     */
    protected function eligibleTicketsQuery(CarbonInterface $cutoff)
    {
        return QueueTicket::query()
            ->terminal()
            ->where('created_at', '<', $cutoff)
            ->where(function ($query) {
                $query
                    ->whereNotNull('notes')
                    ->orWhere('customer_phone', 'not like', '%****%')
                    ->orWhere('customer_name', 'not like', 'Pelanggan #%');
            });
    }

    /**
     * Compute the irreversible, deterministic anonymized attributes (ADR-13).
     *
     * @return array<string, mixed>
     */
    protected function anonymizedAttributes(QueueTicket $ticket): array
    {
        return [
            'customer_name' => 'Pelanggan #'.$ticket->ticket_number,
            'customer_phone' => $this->maskPhoneNumber($ticket->customer_phone),
            'notes' => null,
        ];
    }

    /**
     * Mask a phone number irreversibly while retaining the network prefix
     * (e.g. "081234567890" becomes "0812****0000"). Values too short to
     * carry a subscriber portion are fully masked to avoid leaking digits.
     */
    protected function maskPhoneNumber(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        if (mb_strlen($phone) < 5) {
            return '****';
        }

        return mb_substr($phone, 0, 4).'****0000';
    }
}
