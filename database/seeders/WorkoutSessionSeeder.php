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
        $user = User::where('email', 'ball@gmail.com')->first();
        if (!$user) {
            echo "USER ball@gmail.com NOT FOUND\n";
            return;
        }
        echo "SEEDING FOR USER: " . $user->email . " (ID: " . $user->id . ")\n";

        // Don't clear manually added histories - only add mock ones if missing?
        // Actually, just remove the hard delete of user-generated data.

        $now = Carbon::now();
        $categories = [
            'Push' => ['Bench Press', 'Shoulder Press', 'Tricep Pushdown', 'Dips', 'Lateral Raise'],
            'Pull' => ['Deadlift', 'Lat Pulldown', 'Seated Row', 'Bicep Curl', 'Pull Ups'],
            'Legs' => ['Squat', 'Leg Press', 'Leg Extension', 'Lunge', 'Calf Raise'],
            'Core' => ['Plank', 'Crunches', 'Leg Raise', 'Russian Twist'],
            'Cardio' => ['Treadmill', 'Elliptical', 'Stationary Bike'],
        ];

        $currentWeight = 82.5;

        for ($i = 90; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);

            // Record weight history every ~7 days
            if ($i % 7 === 0) {
                $currentWeight -= rand(-5, 15) / 100;
                \App\Models\WeightHistory::create([
                    'user_id' => $user->id,
                    'weight' => round($currentWeight, 1),
                    'created_at' => $date->copy()->setHour(rand(8, 10)),
                ]);
            }

            // 3 rest days per week
            if (rand(1, 100) <= 40)
                continue;

            $sessionType = array_keys($categories)[rand(0, 4)];
            $exercises = [];
            $numEx = rand(3, 5);
            $selectedEx = (array) array_rand(array_flip($categories[$sessionType]), min($numEx, count($categories[$sessionType])));

            foreach ($selectedEx as $exName) {
                $sets = [];
                $numSets = rand(3, 4);
                for ($s = 1; $s <= $numSets; $s++) {
                    $sets[] = [
                        'weight' => rand(20, 80),
                        'reps' => rand(8, 12),
                    ];
                }

                $image = null;
                $nameLower = strtolower($exName);
                if (str_contains($nameLower, 'bench press'))
                    $image = '/images/equipment/BenchPress.svg';
                else if (str_contains($nameLower, 'dumbbell'))
                    $image = '/images/equipment/Dumbbells.svg';
                else if (str_contains($nameLower, 'treadmill'))
                    $image = '/images/equipment/Treadmill.svg';
                else
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

        $user->update(['weight' => round($currentWeight, 1)]);
    }
}
