<?php

namespace App\Console\Commands;

use App\Services\QueueService;
use Illuminate\Console\Command;

class ExpireNoShowTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queues:expire-noshow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai tiket berstatus dipanggil yang melewati 15 menit menjadi tidak_hadir';

    /**
     * Execute the console command.
     */
    public function handle(QueueService $queueService): int
    {
        $expiredCount = $queueService->expireStaleCalledTickets();

        $this->info(sprintf('Selesai memeriksa tiket dipanggil kedaluwarsa. %d tiket ditandai tidak_hadir.', $expiredCount));

        return self::SUCCESS;
    }
}
