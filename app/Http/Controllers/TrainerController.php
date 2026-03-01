<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\TrainerSchedule;
use App\Models\TrainerVerification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainerController extends Controller
{
    public function index()
    {
        $trainers = Trainer::with(['user', 'courses'])
            ->withAvg('reviews', 'rating')
            ->get()
            ->map(function ($trainer) {
                $todayWork = TrainerVerification::where('trainer_id', $trainer->id)
                    ->where('date', now()->toDateString());

                $trainer->verifications_present_count = (clone $todayWork)->where('status', 'present')->count();
                $trainer->verifications_absent_count = (clone $todayWork)->where('status', 'absent')->count();

                $myVerification = TrainerVerification::where('trainer_id', $trainer->id)
                    ->where('user_id', auth()->id())
                    ->where('date', now()->toDateString())
                    ->first();

                $trainer->has_verified_today = $myVerification ? true : false;
                $trainer->my_verification_status = $myVerification ? $myVerification->status : null;

                // Find active course for the user
                $activeBooking = \App\Models\Booking::where('trainer_id', $trainer->id)
                    ->where('user_id', auth()->id())
                    ->where('status', 'confirmed')
                    ->latest()
                    ->first();

                if ($activeBooking && $activeBooking->course_name) {
                    $trainer->active_course = \App\Models\TrainerCourse::where('trainer_id', $trainer->id)
                        ->where('title', $activeBooking->course_name)
                        ->first();
                }

                return $trainer;
            });

        return Inertia::render('Trainer/List', [
            'trainers' => $trainers
        ]);
    }

    public function showRegistration()
    {
        if (auth()->user()->trainer) {
            return redirect()->route('trainer.dashboard');
        }
        return Inertia::render('TrainerRegistration');
    }

    public function storeRegistration(Request $request)
    {
        $request->validate([
            'specialty' => 'required|string|max:255',
            'bio' => 'required|string',
            'experience_years' => 'required|integer|min:0',
            'price_per_session' => 'required|numeric|min:0',
        ]);

        $trainer = Trainer::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'specialty' => $request->specialty,
                'bio' => $request->bio,
                'experience_years' => $request->experience_years,
                'price_per_session' => $request->price_per_session,
                'gender' => auth()->user()->gender, // Copy from user profile
            ]
        );

        return redirect()->route('trainer.dashboard')->with('success', 'Your trainer profile has been updated!');
    }

    public function book(Request $request)
    {
        $request->validate([
            'trainer_id' => 'required|exists:trainers,id',
            'course_name' => 'nullable|string|max:255',
            'booking_date' => 'required|date|after:yesterday',
            'notes' => 'nullable|string',
        ]);

        $trainer = Trainer::findOrFail($request->trainer_id);
        if ($trainer->user_id === auth()->id()) {
            return back()->withErrors(['trainer_id' => 'You cannot book your own services.']);
        }

        // Create the booking as 'pending' - trainer must manually accept
        $booking = \App\Models\Booking::create([
            'user_id' => auth()->id(),
            'trainer_id' => $request->trainer_id,
            'course_name' => $request->course_name,
            'booking_date' => $request->booking_date,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Booking request sent! Please wait for trainer confirmation.');
    }

    public function storeReview(Request $request)
    {
        $request->validate([
            'trainer_id' => 'required|exists:trainers,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        \App\Models\TrainerReview::updateOrCreate(
            ['user_id' => auth()->id(), 'trainer_id' => $request->trainer_id],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return back()->with('success', 'Review submitted successfully!');
    }

    public function getSchedule(Trainer $trainer)
    {
        $startDate = now()->subMonths(3)->toDateString();
        $endDate = now()->addMonths(6)->toDateString();

        // Get all dates with activity (plans or verifications)
        $scheduleDates = TrainerSchedule::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date');

        $verificationDates = \App\Models\TrainerVerification::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date');

        $relevantDates = $scheduleDates->concat($verificationDates)->unique()->sort()->values();

        // Batch fetch all data for performance
        $allSchedules = TrainerSchedule::where('trainer_id', $trainer->id)
            ->whereIn('date', $relevantDates)
            ->get()
            ->keyBy('date');

        $allVerifications = \App\Models\TrainerVerification::where('trainer_id', $trainer->id)
            ->whereIn('date', $relevantDates)
            ->get()
            ->groupBy('date');

        $schedules = $relevantDates->map(function ($date) use ($allSchedules, $allVerifications, $trainer) {
            $schedule = $allSchedules->get($date);
            $vers = $allVerifications->get($date) ?? collect();

            return [
                'date' => $date,
                'focus_area' => $schedule ? $schedule->focus_area : null,
                'description' => $schedule ? $schedule->description : null,
                'verifications_present_count' => $vers->where('status', 'present')->count(),
                'verifications_absent_count' => $vers->where('status', 'absent')->count(),
                'my_verification_status' => $vers->where('user_id', auth()->id())->first()?->status,
            ];
        });

        return response()->json(['schedules' => $schedules]);
    }

    public function verify(Request $request, Trainer $trainer)
    {
        $request->validate([
            'status' => 'required|in:present,absent',
            'date' => 'required|date'
        ]);

        // Prevent verifying future dates
        if ($request->date > now()->toDateString()) {
            return response()->json(['error' => 'Cannot verify future dates'], 422);
        }

        // Prevent trainer from verifying themselves
        if ($trainer->user_id === auth()->id()) {
            return response()->json(['error' => 'You cannot verify your own attendance.'], 403);
        }

        TrainerVerification::updateOrCreate(
            [
                'trainer_id' => $trainer->id,
                'user_id' => auth()->id(),
                'date' => $request->date,
            ],
            [
                'status' => $request->status
            ]
        );

        return back()->with('success', 'Thank you for your verification!');
    }
}
