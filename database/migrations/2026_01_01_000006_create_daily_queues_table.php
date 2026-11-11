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
        Schema::create('daily_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('restrict');
            $table->date('queue_date');
            $table->unsignedInteger('current_number')->default(0);
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();

            // Prevents race-condition duplicate daily queues per business date
            $table->unique(['business_id', 'queue_date'], 'uq_business_date');

            // Composite key target for engine-level tenant foreign key validation
            $table->unique(['id', 'business_id'], 'uq_dq_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_queues');
    }
};

