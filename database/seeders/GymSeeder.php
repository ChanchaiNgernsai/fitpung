<?php

namespace Database\Seeders;

use App\Models\GymLayout;
use App\Models\User;
use Illuminate\Database\Seeder;

class GymSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        GymLayout::updateOrCreate(
            ['name' => 'Starter Gym HQ'],
            [
                'user_id' => $user->id,
                'location' => 'สันทราย, เชียงใหม่',
                'image_path' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1000&auto=format&fit=crop',
                'is_public' => true,
                'is_approved' => true,
                'room_config' => [
                    'id' => 'rect',
                    'name' => 'Rectangle',
                    'points' => '100,100 900,100 900,700 100,700',
                    'walls' => []
                ],
                'items' => [
                    [
                        'id' => 1714840000001,
                        'name' => 'Treadmill',
                        'type' => 'treadmill',
                        'src' => '/images/equipment/Treadmill.svg',
                        'x' => 250,
                        'y' => 250,
                        'width' => 100,
                        'height' => 200,
                        'rotation' => 0
                    ],
                    [
                        'id' => 1714840000002,
                        'name' => 'Smith Machine',
                        'type' => 'smith',
                        'src' => '/images/equipment/SmithMachine.svg',
                        'x' => 500,
                        'y' => 200,
                        'width' => 150,
                        'height' => 120,
                        'rotation' => 0
                    ],
                    [
                        'id' => 1714840000003,
                        'name' => 'Elliptical',
                        'type' => 'elliptical',
                        'src' => '/images/equipment/Elliptical.svg',
                        'x' => 750,
                        'y' => 250,
                        'width' => 80,
                        'height' => 200,
                        'rotation' => 0
                    ]
                ],
                'description' => 'Perfect for beginners starting their fitness journey.',
                'recommendations' => [
                    [
                        'id' => 'starter_full',
                        'title' => 'Beginner Full Body',
                        'badge' => 'STARTER',
                        'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1000&auto=format&fit=crop',
                        'duration' => '30 MIN',
                        'calories' => '200 KCAL',
                        'level' => 'STARTER',
                        'exercises' => [
                            ['name' => 'Treadmill', 'sets' => 1, 'reps' => '10 min', 'targetWeight' => 'Level 3', 'image' => '/images/gorila/ConcentrationCurl.png'],
                            ['name' => 'Goblet Squat', 'sets' => 3, 'reps' => '12', 'targetWeight' => '5kg', 'image' => '/images/gorila/GobletSquat.png'],
                        ]
                    ]
                ]
            ]
        );

        GymLayout::updateOrCreate(
            ['name' => 'Iron Palace'],
            [
                'user_id' => $user->id,
                'location' => 'สารภี, เชียงใหม่',
                'image_path' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=1000&auto=format&fit=crop',
                'is_public' => true,
                'is_approved' => true,
                'room_config' => [
                    'id' => 'l-shape',
                    'name' => 'L-Shape',
                    'points' => '100,100 900,100 900,400 500,400 500,700 100,700',
                    'walls' => []
                ],
                'items' => [
                    [
                        'id' => 1714840000004,
                        'name' => 'Bench Press',
                        'type' => 'bench',
                        'src' => '/images/equipment/BenchPress.svg',
                        'x' => 250,
                        'y' => 250,
                        'width' => 120,
                        'height' => 150,
                        'rotation' => 0
                    ],
                    [
                        'id' => 1714840000005,
                        'name' => 'Incline Bench Press',
                        'type' => 'incline_bench',
                        'src' => '/images/equipment/DeclineBenchPress.svg',
                        'x' => 450,
                        'y' => 250,
                        'width' => 120,
                        'height' => 150,
                        'rotation' => 0
                    ],
                    [
                        'id' => 1714840000006,
                        'name' => 'Leg Press',
                        'type' => 'leg_press',
                        'src' => '/images/equipment/LegPress.svg',
                        'x' => 700,
                        'y' => 250,
                        'width' => 120,
                        'height' => 180,
                        'rotation' => 0
                    ],
                    [
                        'id' => 1714840000007,
                        'name' => 'Smith Machine',
                        'type' => 'smith',
                        'src' => '/images/equipment/SmithMachine.svg',
                        'x' => 300,
                        'y' => 500,
                        'width' => 150,
                        'height' => 120,
                        'rotation' => 90
                    ]
                ],
                'description' => 'Hardcore gym for serious lifters.',
                'recommendations' => [
                    [
                        'id' => 'power_chest',
                        'title' => 'Heavy Chest',
                        'badge' => 'ADVANCED',
                        'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=1000&auto=format&fit=crop',
                        'duration' => '75 MIN',
                        'calories' => '500 KCAL',
                        'level' => 'ADVANCED',
                        'exercises' => [
                            ['name' => 'Treadmill', 'sets' => 1, 'reps' => '5 min', 'targetWeight' => 'Level 5', 'image' => '/images/gorila/ConcentrationCurl.png'],
                            ['name' => 'Bench Press', 'sets' => 5, 'reps' => '5', 'targetWeight' => '80kg', 'image' => '/images/gorila/InclineBenchPress.png'],
                        ]
                    ]
                ]
            ]
        );
    }
}
