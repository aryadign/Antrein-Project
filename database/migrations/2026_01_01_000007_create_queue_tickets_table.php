<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('restrict');
            $table->unsignedBigInteger('daily_queue_id');
            $table->unsignedBigInteger('service_id');
            $table->string('ticket_number', 20);
            $table->unsignedInteger('sequence_number');
            $table->string('customer_name', 100);
            $table->string('customer_phone', 30)->nullable();
            $table->enum('source', ['online', 'walk_in'])->default('online');
            $table->enum('status', ['menunggu', 'dipanggil', 'sedang_dilayani', 'selesai', 'dilewati', 'dibatalkan'])->default('menunggu');
            $table->string('access_token', 64)->unique('uq_tickets_token');
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('recalled_at')->nullable();
            $table->timestamp('service_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite foreign keys enforcing tenant scoping at MySQL engine level
            $table->foreign(['daily_queue_id', 'business_id'])
                ->references(['id', 'business_id'])
                ->on('daily_queues')
                ->onDelete('restrict');

            $table->foreign(['service_id', 'business_id'])
                ->references(['id', 'business_id'])
                ->on('services')
                ->onDelete('restrict');

            // Unique constraints
            $table->unique(['daily_queue_id', 'ticket_number'], 'uq_daily_ticket');
            $table->unique(['business_id', 'idempotency_key'], 'uq_ticket_idempotency');

            // Performance indexes
            $table->index(['business_id', 'status'], 'idx_business_status');
            $table->index(['daily_queue_id', 'sequence_number'], 'idx_daily_sequence');
            $table->index('source', 'idx_tickets_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};

