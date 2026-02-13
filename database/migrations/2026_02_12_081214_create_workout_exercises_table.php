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
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->onDelete('cascade');
            $table->string('exercise_id')->nullable(); // For future reference if needed
            $table->string('name');
            $table->string('category')->nullable();
            $table->integer('sets')->default(3);
            $table->integer('reps')->default(10);
            $table->decimal('weight', 8, 2)->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
