<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create a default admin account for access
        User::firstOrCreate(
            ['email' => 'admin@fitpung.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('12345678'),
                'is_admin' => true,
            ]
        );

        // Create Ball's account (Regular user/Trainer, not admin)
        User::firstOrCreate(
            ['email' => 'ball@gmail.com'],
            [
                'name' => 'Ball Chanchai',
                'password' => bcrypt('12345678'),
                'weight' => 82.5,
                'height' => 178,
                'goal' => 'Gain Muscle & Strength',
            ]
        );

        $this->call([
            GymSeeder::class,
            EquipmentSeeder::class,
            WorkoutSessionSeeder::class,
            WorkoutPlanSeeder::class,
            AdvancedTrainerSeeder::class,
        ]);
    }
}
