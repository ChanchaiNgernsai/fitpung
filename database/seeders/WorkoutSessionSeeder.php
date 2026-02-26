<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkoutSession;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class WorkoutSessionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user)
            return;

        // Clear existing histories
        \App\Models\WeightHistory::where('user_id', $user->id)->delete();
        \App\Models\WorkoutSession::where('user_id', $user->id)->delete();

        $now = Carbon::now();
        $categories = [
            'Push' => ['Bench Press', 'Shoulder Press', 'Tricep Pushdown', 'Dips', 'Lateral Raise'],
            'Pull' => ['Deadlift', 'Lat Pulldown', 'Seated Row', 'Bicep Curl', 'Pull Ups'],
            'Legs' => ['Squat', 'Leg Press', 'Leg Extension', 'Lunge', 'Calf Raise'],
            'Core' => ['Plank', 'Crunches', 'Leg Raise', 'Russian Twist'],
            'Cardio' => ['Treadmill', 'Elliptical', 'Stationary Bike'],
        ];

        $currentWeight = 85.0; // Initial weight 200 days ago

        for ($i = 200; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);

            // Record weight history every ~10 days
            if ($i % 10 === 0) {
                // Slight downward trend with some randomness
                $currentWeight -= rand(-10, 30) / 100;
                \App\Models\WeightHistory::create([
                    'user_id' => $user->id,
                    'weight' => round($currentWeight, 1),
                    'created_at' => $date->copy()->setHour(rand(8, 10)),
                ]);
            }

            // 2-3 rest days per week (approx 35% chance)
            if (rand(1, 100) <= 35) {
                continue;
            }

            $sessionType = array_keys($categories)[rand(0, 4)];
            $exercises = [];

            // 3-5 exercises per session
            $numEx = rand(3, 5);
            $selectedEx = (array) array_rand(array_flip($categories[$sessionType]), min($numEx, count($categories[$sessionType])));

            foreach ($selectedEx as $exName) {
                $sets = [];
                $numSets = rand(3, 4);
                for ($s = 1; $s <= $numSets; $s++) {
                    $sets[] = [
                        'weight' => rand(10, 100),
                        'reps' => rand(8, 12),
                    ];
                }

                // Image mapping
                $image = null;
                $nameLower = strtolower($exName);
                if (str_contains($nameLower, 'bench press'))
                    $image = '/images/equipment/BenchPress.svg';
                else if (str_contains($nameLower, 'dumbbell'))
                    $image = '/images/equipment/Dumbbells.svg';
                else if (str_contains($nameLower, 'treadmill'))
                    $image = '/images/equipment/Treadmill.svg';
                else if (str_contains($nameLower, 'elliptical'))
                    $image = '/images/equipment/Elliptical.svg';
                else if (str_contains($nameLower, 'leg press'))
                    $image = '/images/equipment/LegPress.svg';
                else if (str_contains($nameLower, 'smith'))
                    $image = '/images/equipment/SmithMachine.svg';

                $exercises[] = [
                    'name' => $exName,
                    'image' => $image,
                    'sets' => $sets,
                ];
            }

            WorkoutSession::create([
                'user_id' => $user->id,
                'workout_date' => $date->toDateString(),
                'data' => [
                    'type' => $sessionType,
                    'exercises' => $exercises,
                    'sets' => array_sum(array_map(fn($ex) => count($ex['sets']), $exercises)),
                ],
            ]);
        }

        // Sync user's current weight attribute
        $user->update(['weight' => round($currentWeight, 1)]);
    }
}
