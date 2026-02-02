<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gym_layouts', function (Blueprint $table) {
            $table->text('location')->nullable()->after('name');
            $table->string('google_map_url')->nullable()->after('location');
            $table->string('image_path')->nullable()->after('google_map_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_layouts', function (Blueprint $table) {
            $table->dropColumn(['location', 'google_map_url', 'image_path']);
        });
    }
};
