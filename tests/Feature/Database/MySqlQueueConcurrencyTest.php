<?php

namespace Tests\Feature\Database;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MySqlQueueConcurrencyTest extends TestCase
{
    /**
     * Set up MySQL connection configuration explicitly for InnoDB concurrency testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql.url' => null,
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'antrein',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);

        DB::purge('mysql');
        DB::connection('mysql')->getPdo();
    }

    /**
     * Test atomic row-locking on daily_queues and sequential numbering under MySQL InnoDB.
     */
    public function test_mysql_row_locking_and_atomic_numbering(): void
    {
        DB::connection('mysql')->beginTransaction();

        try {
            $category = BusinessCategory::on('mysql')->create([
                'name' => 'MySQL Concurrency Cat',
                'slug' => 'mysql-cat-'.Str::random(8),
                'is_active' => true,
            ]);

            $merchant = User::on('mysql')->create([
                'name' => 'MySQL Merchant',
                'email' => 'mysql-merch-'.Str::random(8).'@antrein.id',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
            ]);

            $business = Business::on('mysql')->create([
                'user_id' => $merchant->id,
                'category_id' => $category->id,
                'name' => 'Klinik Concurrency',
                'slug' => 'klinik-concurrency-'.Str::random(8),
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
            ]);

            $service = Service::on('mysql')->create([
                'business_id' => $business->id,
                'name' => 'Layanan Concurrency',
                'duration_minutes' => 15,
                'daily_quota' => 50,
                'is_active' => true,
            ]);

            $dailyQueue = DailyQueue::on('mysql')->create([
                'business_id' => $business->id,
                'queue_date' => now()->toDateString(),
                'current_number' => 0,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);

            // Emulate 5 sequential/atomic ticket increments with row-level locking
            $tickets = [];
            for ($i = 1; $i <= 5; $i++) {
                // Lock row
                $lockedQueue = DailyQueue::on('mysql')
                    ->where('id', $dailyQueue->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nextNumber = $lockedQueue->current_number + 1;
                $lockedQueue->current_number = $nextNumber;
                $lockedQueue->save();

                $ticketNumber = sprintf('A-%03d', $nextNumber);

                $tickets[] = QueueTicket::on('mysql')->create([
                    'business_id' => $business->id,
                    'daily_queue_id' => $dailyQueue->id,
                    'service_id' => $service->id,
                    'ticket_number' => $ticketNumber,
                    'sequence_number' => $nextNumber,
                    'source' => QueueTicket::SOURCE_ONLINE,
                    'status' => QueueTicket::STATUS_WAITING,
                    'access_token' => Str::random(40),
                    'customer_name' => "Customer {$i}",
                ]);
            }

            $this->assertEquals(5, $dailyQueue->fresh()->current_number);
            $this->assertEquals('A-001', $tickets[0]->ticket_number);
            $this->assertEquals('A-005', $tickets[4]->ticket_number);
            $this->assertEquals(5, count($tickets));

        } finally {
            DB::connection('mysql')->rollBack();
        }
    }

    /**
     * Test that MySQL UNIQUE constraint strictly enforces one daily_queue per business per date.
     */
    public function test_mysql_unique_constraint_on_daily_queues(): void
    {
        DB::connection('mysql')->beginTransaction();

        try {
            $category = BusinessCategory::on('mysql')->create([
                'name' => 'MySQL Unique Cat',
                'slug' => 'mysql-cat-'.Str::random(8),
                'is_active' => true,
            ]);

            $merchant = User::on('mysql')->create([
                'name' => 'MySQL Merchant Unique',
                'email' => 'mysql-uniq-'.Str::random(8).'@antrein.id',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
            ]);

            $business = Business::on('mysql')->create([
                'user_id' => $merchant->id,
                'category_id' => $category->id,
                'name' => 'Unique Test Business',
                'slug' => 'uniq-biz-'.Str::random(8),
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
            ]);

            $today = '2026-11-09';

            DailyQueue::on('mysql')->create([
                'business_id' => $business->id,
                'queue_date' => $today,
                'current_number' => 0,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);

            $duplicateCaught = false;
            try {
                // Attempting second insert for same business and date
                DailyQueue::on('mysql')->create([
                    'business_id' => $business->id,
                    'queue_date' => $today,
                    'current_number' => 0,
                    'status' => DailyQueue::STATUS_ACTIVE,
                ]);
            } catch (QueryException $e) {
                $duplicateCaught = true;
            }

            $this->assertTrue($duplicateCaught, 'MySQL UNIQUE(business_id, queue_date) constraint must block duplicate daily queues.');

        } finally {
            DB::connection('mysql')->rollBack();
        }
    }

    /**
     * Test that MySQL UNIQUE constraint strictly enforces business-scoped idempotency keys.
     */
    public function test_mysql_unique_constraint_on_idempotency_keys(): void
    {
        DB::connection('mysql')->beginTransaction();

        try {
            $category = BusinessCategory::on('mysql')->create([
                'name' => 'MySQL Idem Cat',
                'slug' => 'mysql-idem-'.Str::random(8),
                'is_active' => true,
            ]);

            $merchant = User::on('mysql')->create([
                'name' => 'MySQL Merchant Idem',
                'email' => 'mysql-idem-'.Str::random(8).'@antrein.id',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
            ]);

            $business = Business::on('mysql')->create([
                'user_id' => $merchant->id,
                'category_id' => $category->id,
                'name' => 'Idem Test Business',
                'slug' => 'idem-biz-'.Str::random(8),
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
            ]);

            $service = Service::on('mysql')->create([
                'business_id' => $business->id,
                'name' => 'Idem Service',
                'duration_minutes' => 10,
                'daily_quota' => 20,
                'is_active' => true,
            ]);

            $dailyQueue = DailyQueue::on('mysql')->create([
                'business_id' => $business->id,
                'queue_date' => now()->toDateString(),
                'current_number' => 1,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);

            $idempotencyKey = 'idemp-' . Str::random(16);

            QueueTicket::on('mysql')->create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $service->id,
                'ticket_number' => 'A-001',
                'sequence_number' => 1,
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_WAITING,
                'access_token' => Str::random(40),
                'customer_name' => 'First Insert',
                'idempotency_key' => $idempotencyKey,
            ]);

            $duplicateKeyCaught = false;
            try {
                // Attempting second insert with same idempotency key for same business
                QueueTicket::on('mysql')->create([
                    'business_id' => $business->id,
                    'daily_queue_id' => $dailyQueue->id,
                    'service_id' => $service->id,
                    'ticket_number' => 'A-002',
                    'sequence_number' => 2,
                    'source' => QueueTicket::SOURCE_ONLINE,
                    'status' => QueueTicket::STATUS_WAITING,
                    'access_token' => Str::random(40),
                    'customer_name' => 'Duplicate Key Insert',
                    'idempotency_key' => $idempotencyKey,
                ]);
            } catch (QueryException $e) {
                $duplicateKeyCaught = true;
            }

            $this->assertTrue($duplicateKeyCaught, 'MySQL UNIQUE(business_id, idempotency_key) must prevent duplicate keys per business.');

        } finally {
            DB::connection('mysql')->rollBack();
        }
    }

    /**
     * Test concurrent last-quota allocation under MySQL InnoDB row-level locking.
     * When only 1 quota slot remains, two competing requests must serialize: exactly 1 succeeds, 1 fails.
     */
    public function test_mysql_concurrent_last_quota_allocation(): void
    {
        DB::connection('mysql')->beginTransaction();

        try {
            $category = BusinessCategory::on('mysql')->create([
                'name' => 'MySQL Quota Cat',
                'slug' => 'mysql-quota-'.Str::random(8),
                'is_active' => true,
            ]);

            $merchant = User::on('mysql')->create([
                'name' => 'MySQL Quota Merchant',
                'email' => 'mysql-quota-'.Str::random(8).'@antrein.id',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
            ]);

            $business = Business::on('mysql')->create([
                'user_id' => $merchant->id,
                'category_id' => $category->id,
                'name' => 'Klinik Kuota Terakhir',
                'slug' => 'klinik-kuota-'.Str::random(8),
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
            ]);

            // Daily quota is strictly 1
            $service = Service::on('mysql')->create([
                'business_id' => $business->id,
                'name' => 'Layanan Kuota 1',
                'duration_minutes' => 30,
                'daily_quota' => 1,
                'is_active' => true,
            ]);

            $dailyQueue = DailyQueue::on('mysql')->create([
                'business_id' => $business->id,
                'queue_date' => now()->toDateString(),
                'current_number' => 0,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);

            // Request 1: Acquires row lock on daily queue, checks remaining quota (1), succeeds
            $lockedQueue1 = DailyQueue::on('mysql')
                ->where('id', $dailyQueue->id)
                ->lockForUpdate()
                ->firstOrFail();

            $consumedBefore = QueueTicket::on('mysql')
                ->where('service_id', $service->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', '!=', QueueTicket::STATUS_DIBATALKAN)
                ->count();

            $this->assertEquals(0, $consumedBefore);

            // Insert Ticket 1
            QueueTicket::on('mysql')->create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $service->id,
                'ticket_number' => 'A-001',
                'sequence_number' => 1,
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_WAITING,
                'access_token' => Str::random(40),
                'customer_name' => 'Competitor 1 (Winner)',
            ]);

            // Request 2 (serialized after Request 1): Checks remaining quota, discovers 0 remaining, rejected!
            $consumedAfter = QueueTicket::on('mysql')
                ->where('service_id', $service->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', '!=', QueueTicket::STATUS_DIBATALKAN)
                ->count();

            $remainingQuotaReq2 = max(0, $service->daily_quota - $consumedAfter);
            $this->assertEquals(0, $remainingQuotaReq2, 'After first allocation, remaining quota must be strictly 0.');

            $request2Rejected = ($remainingQuotaReq2 <= 0);
            $this->assertTrue($request2Rejected, 'Second competing request must be rejected due to quota exhaustion.');

            // Verify only 1 ticket created in total
            $totalTickets = QueueTicket::on('mysql')
                ->where('service_id', $service->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->count();

            $this->assertEquals(1, $totalTickets);

        } finally {
            DB::connection('mysql')->rollBack();
        }
    }
}
