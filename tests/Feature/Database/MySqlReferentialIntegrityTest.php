<?php

namespace Tests\Feature\Database;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MySqlReferentialIntegrityTest extends TestCase
{
    /**
     * Verify MySQL InnoDB foreign key restrictions and composite constraints.
     */
    public function test_mysql_composite_foreign_keys_and_referential_integrity(): void
    {
        // Configure mysql connection explicitly
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

        DB::connection('mysql')->beginTransaction();

        try {
            $category = BusinessCategory::on('mysql')->create([
                'name' => 'MySQL Test Cat',
                'slug' => 'mysql-test-cat-'.Str::random(8),
                'is_active' => true,
            ]);

            $user = User::on('mysql')->create([
                'name' => 'MySQL Test User',
                'email' => 'mysql-'.Str::random(8).'@test.id',
                'password' => bcrypt('secret123'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
            ]);

            $business = Business::on('mysql')->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => 'MySQL Test Biz',
                'slug' => 'mysql-test-biz-'.Str::random(8),
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
            ]);

            $service = Service::on('mysql')->create([
                'business_id' => $business->id,
                'name' => 'MySQL Test Svc',
                'duration_minutes' => 30,
                'price' => 25000,
                'is_active' => true,
            ]);

            $dailyQueue = DailyQueue::on('mysql')->create([
                'business_id' => $business->id,
                'queue_date' => now()->toDateString(),
                'current_number' => 1,
                'status' => DailyQueue::STATUS_ACTIVE,
            ]);

            $ticket = QueueTicket::on('mysql')->create([
                'business_id' => $business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $service->id,
                'ticket_number' => 'M-001',
                'sequence_number' => 1,
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'access_token' => Str::random(40),
                'customer_name' => 'MySQL Customer',
                'customer_phone' => '081234567890',
            ]);

            $this->assertNotNull($ticket->id);

            // Test 1: Deleting business with restrict policy on tickets must be rejected in MySQL
            $caughtException = false;
            try {
                DB::connection('mysql')->table('businesses')->where('id', $business->id)->delete();
            } catch (QueryException $e) {
                $caughtException = true;
            }
            $this->assertTrue($caughtException, 'MySQL InnoDB foreign key RESTRICT prevented destructive deletion of referenced business.');

            // Test 2: Composite foreign key mismatch (referencing daily_queue of another business) must fail in MySQL
            $otherUser = User::on('mysql')->create([
                'name' => 'Other Biz Owner',
                'email' => 'other-'.Str::random(8).'@test.id',
                'password' => bcrypt('secret123'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
            ]);

            $otherBusiness = Business::on('mysql')->create([
                'user_id' => $otherUser->id,
                'category_id' => $category->id,
                'name' => 'Other Biz',
                'slug' => 'other-biz-'.Str::random(8),
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
            ]);

            $mismatchedFkCaught = false;
            try {
                // Trying to insert ticket with business_id of business 1, but daily_queue belonging to otherBusiness
                $otherDailyQueue = DailyQueue::on('mysql')->create([
                    'business_id' => $otherBusiness->id,
                    'queue_date' => now()->toDateString(),
                    'current_number' => 1,
                    'status' => DailyQueue::STATUS_ACTIVE,
                ]);

                DB::connection('mysql')->table('queue_tickets')->insert([
                    'business_id' => $business->id, // Mismatched!
                    'daily_queue_id' => $otherDailyQueue->id, // Belongs to otherBusiness!
                    'service_id' => $service->id,
                    'ticket_number' => 'M-002',
                    'sequence_number' => 2,
                    'source' => 'online',
                    'status' => 'menunggu',
                    'access_token' => Str::random(40),
                    'customer_name' => 'Test',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                $mismatchedFkCaught = true;
            }

            $this->assertTrue($mismatchedFkCaught, 'MySQL composite foreign key fk_ticket_daily_queue [daily_queue_id, business_id] strictly prevented cross-tenant queue ticket linkage!');

        } finally {
            DB::connection('mysql')->rollBack();
        }
    }
}
