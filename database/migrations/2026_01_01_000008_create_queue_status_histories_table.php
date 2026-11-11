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
        Schema::create('queue_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_ticket_id')->constrained('queue_tickets')->onDelete('restrict');
            $table->enum('event_type', ['status_changed', 'ticket_recalled', 'ticket_skipped', 'ticket_cancelled', 'note_added'])->default('status_changed');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->enum('actor_type', ['merchant', 'customer', 'system'])->default('merchant');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('queue_ticket_id', 'idx_history_ticket');
            $table->index('event_type', 'idx_history_event');
            $table->index('created_at', 'idx_history_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_status_histories');
    }
};

