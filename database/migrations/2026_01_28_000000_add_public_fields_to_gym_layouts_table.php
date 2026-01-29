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
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->nullable(); // e.g., Daily interaction rate or Membership
            $table->string('thumbnail_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_layouts', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'description', 'price', 'thumbnail_path']);
        });
    }
};
