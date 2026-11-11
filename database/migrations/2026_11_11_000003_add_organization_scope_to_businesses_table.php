<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('business_organizations')
                ->restrictOnDelete();
            $table->boolean('is_active')->default(true)->after('status');
            $table->index('user_id');
        });

        // Backfill: every legacy business becomes an active branch of a default organization.
        // Slugs, public URLs, queue numbers and ticket tokens are never modified.
        $businesses = DB::table('businesses')->orderBy('id')->get();

        foreach ($businesses as $business) {
            $organizationId = DB::table('business_organizations')->insertGetId([
                'owner_user_id' => $business->user_id,
                'name' => $business->name,
                'slug' => $this->uniqueOrganizationSlug((string) $business->slug),
                'description' => $business->description,
                'timezone' => $business->timezone ?: 'Asia/Makassar',
                'is_active' => true,
                'created_at' => $business->created_at ?? now(),
                'updated_at' => $business->updated_at ?? now(),
            ]);

            DB::table('businesses')
                ->where('id', $business->id)
                ->update(['organization_id' => $organizationId]);

            if ($business->user_id) {
                DB::table('organization_memberships')->insertOrIgnore([
                    'organization_id' => $organizationId,
                    'user_id' => $business->user_id,
                    'role' => 'owner',
                    'is_active' => true,
                    'joined_at' => $business->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Legacy ownership is now expressed through organization membership + branch membership,
        // so a user may own more than one branch.
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $duplicateOwner = DB::table('businesses')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateOwner) {
            throw new RuntimeException(
                'Rollback dibatalkan: terdapat lebih dari satu cabang untuk user_id yang sama. '
                .'Hapus/arsipkan cabang tambahan sebelum mengembalikan unique constraint lama.'
            );
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'is_active']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Generate a unique organization slug without changing the legacy business slug.
     */
    private function uniqueOrganizationSlug(string $baseSlug): string
    {
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'organisasi-'.Str::lower(Str::random(6));
        $slug = $baseSlug;
        $counter = 1;

        while (DB::table('business_organizations')->where('slug', $slug)->exists()) {
            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }

        return $slug;
    }
};
