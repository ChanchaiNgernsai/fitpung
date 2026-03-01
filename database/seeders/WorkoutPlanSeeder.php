<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Database\Seeder;

class WorkoutPlanSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'ball@gmail.com')->first();
        if (!$user)
            return;

        // Clear only Ball's plans to avoid duplication on repeated seed
        WorkoutPlan::where('user_id', $user->id)->delete();

        WorkoutPlan::create([
            'user_id' => $user->id,
            'name' => 'Morning Shred',
            'data' => [
                'gymName' => 'Saved Plan',
                'badge' => 'USER SAVED',
                'duration' => '45 MIN',
                'calories' => '300',
                'isCustom' => true,
                'exercises' => [
                    ['name' => 'Treadmill', 'sets' => 1, 'reps' => '10 min', 'targetWeight' => 'Level 5', 'image' => '/images/equipment/Treadmill.svg'],
                    ['name' => 'Bench Press', 'sets' => 3, 'reps' => '12', 'targetWeight' => '40kg', 'image' => '/images/equipment/BenchPress.svg'],
                ]
            ]
        ]);

        WorkoutPlan::create([
            'user_id' => $user->id,
            'name' => 'Power Legs',
            'data' => [
                'gymName' => 'Saved Plan',
                'badge' => 'USER SAVED',
                'duration' => '60 MIN',
                'calories' => '450',
                'isCustom' => true,
                'exercises' => [
                    ['name' => 'Squat', 'sets' => 4, 'reps' => '8', 'targetWeight' => '60kg', 'image' => '/images/equipment/SmithMachine.svg'],
                    ['name' => 'Leg Press', 'sets' => 3, 'reps' => '12', 'targetWeight' => '120kg', 'image' => '/images/equipment/LegPress.svg'],
                ]
            ]
        ]);
    }
}
