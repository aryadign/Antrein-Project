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
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->timestamps();
        });

        // Seed initial platform defaults
        $now = now();
        DB::table('platform_settings')->insert([
            ['key' => 'platform_name', 'value' => 'AntreIn', 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'platform_tagline', 'value' => 'Solusi Antrean Digital UMKM Terpadu', 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact_email', 'value' => 'support@antrein.id', 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact_phone', 'value' => '+62 812-3456-7890', 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_timezone', 'value' => 'Asia/Makassar', 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'platform_logo_path', 'value' => null, 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};

