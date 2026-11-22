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
        Schema::table('business_categories', function (Blueprint $table) {
            $table->string('icon', 50)->nullable()->after('description');
            $table->string('color_theme', 30)->nullable()->after('icon');
            $table->string('cover_path', 255)->nullable()->after('color_theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color_theme', 'cover_path']);
        });
    }
};
