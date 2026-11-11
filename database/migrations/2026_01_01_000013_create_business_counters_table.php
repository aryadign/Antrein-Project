<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'is_active']);
        });

        Schema::create('counter_operator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_counter_id')->constrained('business_counters')->cascadeOnDelete();
            $table->foreignId('business_membership_id')->constrained('business_memberships')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_counter_id', 'business_membership_id'], 'uq_counter_membership');
        });

        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->foreignId('counter_id')->nullable()->after('service_id')->constrained('business_counters')->nullOnDelete();
            $table->index(['business_id', 'counter_id', 'status']);
        });

        // Data migration for backward compatibility:
        // Provision default 'Loket 1' for all existing businesses and assign memberships
        $businesses = DB::table('businesses')->get();
        foreach ($businesses as $business) {
            $counterId = DB::table('business_counters')->insertGetId([
                'business_id' => $business->id,
                'name' => 'Loket 1',
                'code' => 'L-01',
                'description' => 'Loket pelayanan utama',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('business_memberships')) {
                $memberships = DB::table('business_memberships')
                    ->where('business_id', $business->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($memberships as $membership) {
                    DB::table('counter_operator_assignments')->insertOrIgnore([
                        'business_counter_id' => $counterId,
                        'business_membership_id' => $membership->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Map existing active tickets to default counter
            DB::table('queue_tickets')
                ->where('business_id', $business->id)
                ->whereIn('status', ['dipanggil', 'sedang_dilayani'])
                ->update(['counter_id' => $counterId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropForeign(['counter_id']);
            $table->dropIndex(['business_id', 'counter_id', 'status']);
            $table->dropColumn('counter_id');
        });

        Schema::dropIfExists('counter_operator_assignments');
        Schema::dropIfExists('business_counters');
    }
};
