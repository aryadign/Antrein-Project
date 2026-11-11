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
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->string('customer_email', 255)->nullable()->after('customer_phone');
            $table->timestamp('customer_email_consented_at')->nullable()->after('customer_email');
            $table->string('last_notified_status', 30)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'customer_email',
                'customer_email_consented_at',
                'last_notified_status',
            ]);
        });
    }
};

