<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_memberships', function (Blueprint $table) {
            if (! Schema::hasColumn('business_memberships', 'staff_password_encrypted')) {
                $table->text('staff_password_encrypted')->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_memberships', function (Blueprint $table) {
            if (Schema::hasColumn('business_memberships', 'staff_password_encrypted')) {
                $table->dropColumn('staff_password_encrypted');
            }
        });
    }
};
