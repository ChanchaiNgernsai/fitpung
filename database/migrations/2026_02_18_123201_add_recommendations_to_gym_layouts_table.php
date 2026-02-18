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
            $table->json('recommendations')->nullable()->after('price_list');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_layouts', function (Blueprint $table) {
            $table->dropColumn('recommendations');
        });
    }
};
