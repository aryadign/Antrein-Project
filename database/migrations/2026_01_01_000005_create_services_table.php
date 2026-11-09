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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(15);
            $table->decimal('price', 12, 2)->default(0.00);
            $table->unsignedInteger('daily_quota')->nullable();
            $table->boolean('is_active')->default(true)->index('idx_services_active');
            $table->timestamps();
            $table->softDeletes();

            // Composite key target for engine-level tenant foreign key validation
            $table->unique(['id', 'business_id'], 'uq_svc_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

