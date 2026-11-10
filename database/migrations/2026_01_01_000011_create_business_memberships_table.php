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
        Schema::create('business_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            // user_id is unique: enforces that a user can only belong to at most one business in AntreIn
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('operator'); // 'owner', 'operator'
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'role']);
            $table->index(['business_id', 'is_active']);
        });

        // Seed existing owners into business_memberships for full backward compatibility
        $existingBusinesses = DB::table('businesses')->whereNotNull('user_id')->get(['id', 'user_id', 'created_at']);
        foreach ($existingBusinesses as $b) {
            DB::table('business_memberships')->insertOrIgnore([
                'business_id' => $b->id,
                'user_id' => $b->user_id,
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => $b->created_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_memberships');
    }
};

