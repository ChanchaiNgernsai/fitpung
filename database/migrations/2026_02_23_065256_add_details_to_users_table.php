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
        Schema::table('users', function (Blueprint $table) {
            $table->float('weight')->nullable();
            $table->float('height')->nullable();
            $table->string('gender')->nullable();
            $table->string('goal')->nullable();
        });

        // Add dummy data for existing users
        \App\Models\User::all()->each(function ($user) {
            $user->update([
                'weight' => rand(60, 95),
                'height' => rand(160, 190),
                'gender' => collect(['Male', 'Female'])->random(),
                'goal' => collect(['Muscle Gain', 'Lose Weight', 'Keep Fit'])->random(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['weight', 'height', 'gender', 'goal']);
        });
    }
};
