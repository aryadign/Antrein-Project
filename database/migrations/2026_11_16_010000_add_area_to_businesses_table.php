<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'area')) {
                $table->string('area', 100)->nullable()->after('address');
                $table->index('area', 'idx_businesses_area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'area')) {
                $table->dropIndex('idx_businesses_area');
                $table->dropColumn('area');
            }
        });
    }
};
