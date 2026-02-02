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
            $table->json('operating_hours')->nullable()->after('items');
            $table->json('holidays')->nullable()->after('operating_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_layouts', function (Blueprint $table) {
            $table->dropColumn(['operating_hours', 'holidays']);
        });
    }
};
