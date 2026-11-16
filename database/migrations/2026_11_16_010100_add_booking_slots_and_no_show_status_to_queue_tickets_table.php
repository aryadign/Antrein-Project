<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE queue_tickets MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'menunggu'");
        } else {
            Schema::table('queue_tickets', function (Blueprint $table) {
                $table->string('status', 30)->default('menunggu')->change();
            });
        }

        Schema::table('queue_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('queue_tickets', 'booking_slot_start')) {
                $table->timestamp('booking_slot_start')->nullable()->after('sequence_number');
            }

            if (! Schema::hasColumn('queue_tickets', 'booking_slot_end')) {
                $table->timestamp('booking_slot_end')->nullable()->after('booking_slot_start');
            }

            $table->index(
                ['daily_queue_id', 'status', 'booking_slot_start', 'sequence_number'],
                'idx_queue_slot_fifo'
            );
        });
    }

    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_queue_slot_fifo');

            if (Schema::hasColumn('queue_tickets', 'booking_slot_end')) {
                $table->dropColumn('booking_slot_end');
            }

            if (Schema::hasColumn('queue_tickets', 'booking_slot_start')) {
                $table->dropColumn('booking_slot_start');
            }
        });
    }
};
