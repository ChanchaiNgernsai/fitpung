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
            $table->json('promotions')->nullable()->after('holidays');
            $table->json('price_list')->nullable()->after('promotions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_layouts', function (Blueprint $table) {
            $table->dropColumn(['promotions', 'price_list']);
        });
    }
};
