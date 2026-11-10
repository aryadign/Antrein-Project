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
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('is_display_enabled')->default(false)->after('is_suspended');
            $table->string('display_token', 100)->nullable()->after('is_display_enabled');
            $table->string('display_token_hash', 64)->nullable()->unique()->after('display_token');
            $table->timestamp('display_token_created_at')->nullable()->after('display_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'is_display_enabled',
                'display_token',
                'display_token_hash',
                'display_token_created_at',
            ]);
        });
    }
};

