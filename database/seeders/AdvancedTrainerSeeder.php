<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\ClientPackage;
use App\Models\ClientProgressMetric;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdvancedTrainerSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Ball's Account exists
        $ballUser = User::updateOrCreate(
            ['email' => 'ball@gmail.com'],
            [
                'name' => 'Ball Chanchai',
                'password' => Hash::make('12345678'),
                'weight' => 82.5,
                'height' => 178,
                'goal' => 'Gain Muscle & Strength',
            ]
        );

        $trainer = Trainer::updateOrCreate(
            ['user_id' => $ballUser->id],
            [
                'specialty' => 'Advanced Trainer',
                'bio' => 'Professional trainer focused on results and sustainable health.',
                'experience_years' => 10,
                'price_per_session' => 1500,
                'gender' => 'Male',
                'is_verified' => true,
            ]
        );

        // 2. Add minimal mock clients for feature testing
        $clients = [
            ['name' => 'John Doe', 'email' => 'john@test.com', 'weight' => 85, 'pkg_total' => 20, 'pkg_used' => 12],
            ['name' => 'Sarah Smith', 'email' => 'sarah@test.com', 'weight' => 62, 'pkg_total' => 10, 'pkg_used' => 5],
        ];

        foreach ($clients as $cData) {
            $clientUser = User::updateOrCreate(
                ['email' => $cData['email']],
                [
                    'name' => $cData['name'],
                    'password' => Hash::make('12345678'),
                    'weight' => $cData['weight'],
                ]
            );

            ClientPackage::updateOrCreate(
                ['user_id' => $clientUser->id, 'trainer_id' => $trainer->id],
                [
                    'total_hours' => $cData['pkg_total'],
                    'used_hours' => $cData['pkg_used'],
                    'status' => 'active',
                ]
            );

            // Upcoming booking
            Booking::updateOrCreate(
                [
                    'trainer_id' => $trainer->id,
                    'user_id' => $clientUser->id,
                    'booking_date' => now()->addDays(rand(1, 3))->setHour(rand(10, 16))->setMinute(0)
                ],
                [
                    'status' => 'confirmed',
                    'notes' => 'Weekly training session',
                ]
            );
        }
    }
}
