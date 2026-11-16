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
        Schema::table('staff_invitations', function (Blueprint $table) {
            $table->foreignId('business_counter_id')
                ->nullable()
                ->after('role')
                ->constrained('business_counters')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_counter_id');
        });
    }
};
