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
        Schema::table('business_memberships', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('business_id')
                ->constrained('business_organizations')
                ->cascadeOnDelete();
            $table->index(['user_id', 'is_active'], 'idx_membership_user_active');
        });

        // Backfill organization scope for every existing branch membership.
        DB::statement('
            UPDATE business_memberships
            SET organization_id = (
                SELECT businesses.organization_id
                FROM businesses
                WHERE businesses.id = business_memberships.business_id
            )
        ');

        // Memberships are unique per branch, not per user: multi-branch staff is now supported.
        Schema::table('business_memberships', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->unique(['business_id', 'user_id'], 'uq_membership_business_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $duplicateMember = DB::table('business_memberships')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateMember) {
            throw new RuntimeException(
                'Rollback dibatalkan: terdapat user yang menjadi anggota lebih dari satu cabang. '
                .'Hapus keanggotaan tambahan sebelum mengembalikan unique constraint lama.'
            );
        }

        Schema::table('business_memberships', function (Blueprint $table) {
            $table->dropUnique('uq_membership_business_user');
            $table->dropIndex('idx_membership_user_active');
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('business_memberships', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }
};
