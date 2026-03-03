<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ClientPackage;
use App\Models\ClientProgressMetric;
use App\Models\Trainer;
use App\Models\TrainerSession;
use App\Models\TrainerSchedule;
use App\Models\TrainerCourse;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TrainerManagementController extends Controller
{
    public function index()
    {
        $trainer = Trainer::with('user')->where('user_id', Auth::id())->firstOrFail();

        // Safety Sync: Ensure all confirmed bookings have a corresponding active package
        $confirmedBookings = Booking::where('trainer_id', $trainer->id)
            ->where('status', 'confirmed')
            ->get();

        foreach ($confirmedBookings as $booking) {
            ClientPackage::updateOrCreate(
                [
                    'user_id' => $booking->user_id,
                    'trainer_id' => $trainer->id,
                    'course_name' => $booking->course_name
                ],
                ['status' => 'active', 'total_hours' => 10]
            );
        }

        $clients = ClientPackage::with('user')
            ->where('trainer_id', $trainer->id)
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->get();

        $bookings = Booking::with('user')
            ->where('trainer_id', $trainer->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('booking_date', 'asc')
            ->get();

        $courses = TrainerCourse::where('trainer_id', $trainer->id)->get();

        $verifications = \App\Models\TrainerVerification::with('user')
            ->where('trainer_id', $trainer->id)
            ->orderBy('date', 'desc')
            ->limit(50)
            ->get();

        $gyms = \App\Models\GymLayout::where('is_public', true)
            ->where('is_approved', true)
            ->get();

        return Inertia::render('Trainer/Dashboard', [
            'trainer' => $trainer,
            'clients' => $clients,
            'bookings' => $bookings,
            'courses' => $courses,
            'verifications' => $verifications,
            'gyms' => $gyms,
        ]);
    }

    public function storeCourse(Request $request)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer)
            abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lesson_plan' => 'nullable|array',
            'duration' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'hours' => 'nullable|integer|min:1',
        ]);

        \App\Models\TrainerCourse::create([
            'trainer_id' => $trainer->id,
            'title' => $request->title,
            'description' => $request->description,
            'lesson_plan' => $request->lesson_plan,
            'duration' => $request->duration,
            'level' => $request->level,
            'price' => $request->price,
            'hours' => $request->hours ?? 10,
        ]);

        return back()->with('success', 'Course created successfully');
    }

    public function updateCourse(Request $request, \App\Models\TrainerCourse $course)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer || $course->trainer_id !== $trainer->id)
            abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lesson_plan' => 'nullable|array',
            'duration' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'hours' => 'nullable|integer|min:1',
        ]);

        $course->update($request->only(['title', 'description', 'lesson_plan', 'duration', 'level', 'price', 'hours']));

        return back()->with('success', 'Course updated successfully');
    }

    public function deleteCourse(\App\Models\TrainerCourse $course)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer || $course->trainer_id !== $trainer->id)
            abort(403);

        $course->delete();

        return back()->with('success', 'Course deleted successfully');
    }

    public function updateBooking(Request $request, Booking $booking)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer || $booking->trainer_id !== $trainer->id) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed',
        ]);

        $booking->update(['status' => $request->status]);

        // Real Enrollment Flow: If confirmed, ensure a ClientPackage exists
        if ($request->status === 'confirmed') {
            $totalHours = 10;
            if ($booking->course_name) {
                $course = \App\Models\TrainerCourse::where('trainer_id', $trainer->id)
                    ->where('title', $booking->course_name)
                    ->first();
                if ($course) {
                    $totalHours = $course->hours;
                }
            }

            ClientPackage::updateOrCreate(
                [
                    'user_id' => $booking->user_id,
                    'trainer_id' => $trainer->id,
                    'course_name' => $booking->course_name,
                ],
                [
                    'total_hours' => $totalHours,
                    'used_hours' => 0,
                    'status' => 'active',
                ]
            );
        }

        return back()->with('success', 'Booking updated successfully');
    }

    public function deleteBooking(Booking $booking)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer || $booking->trainer_id !== $trainer->id) {
            abort(403);
        }

        // Also delete the client package if it exists
        ClientPackage::where('user_id', $booking->user_id)
            ->where('trainer_id', $trainer->id)
            ->where('course_name', $booking->course_name)
            ->delete();

        $booking->delete();
        return back()->with('success', 'Booking removed.');
    }

    public function removeClient(ClientPackage $clientPackage)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer || $clientPackage->trainer_id !== $trainer->id) {
            abort(403);
        }

        // Also delete the associated booking if it exists
        Booking::where('user_id', $clientPackage->user_id)
            ->where('trainer_id', $trainer->id)
            ->where('course_name', $clientPackage->course_name)
            ->delete();

        $clientPackage->delete();
        return back()->with('success', 'Member removed from roster.');
    }

    public function clientDetails(User $user)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer)
            abort(403);

        $package = ClientPackage::where('user_id', $user->id)
            ->where('trainer_id', $trainer->id)
            ->first();

        $metrics = ClientProgressMetric::where('user_id', $user->id)
            ->where('trainer_id', $trainer->id)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'user' => $user,
            'package' => $package,
            'metrics' => $metrics,
        ]);
    }

    public function recordSession(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_name' => 'nullable|string',
            'hours' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'metrics' => 'nullable|array',
        ]);

        $trainer = Auth::user()->trainer;
        if (!$trainer)
            abort(403);

        $query = ClientPackage::where('user_id', $request->user_id)
            ->where('trainer_id', $trainer->id)
            ->where('status', 'active');

        if ($request->course_name) {
            $query->where('course_name', $request->course_name);
        }

        $package = $query->firstOrFail();

        $package->increment('used_hours', $request->hours);

        if ($package->used_hours >= $package->total_hours) {
            $package->update(['status' => 'completed']);
        }

        // Save session history
        TrainerSession::create([
            'trainer_id' => $trainer->id,
            'user_id' => $request->user_id,
            'hours' => $request->hours,
            'notes' => $request->notes,
            'type' => 'Standard'
        ]);

        if ($request->metrics) {
            foreach ($request->metrics as $name => $value) {
                if ($value === null || $value === '')
                    continue;
                ClientProgressMetric::create([
                    'user_id' => $request->user_id,
                    'trainer_id' => $trainer->id,
                    'metric_name' => $name,
                    'metric_value' => $value,
                    'recorded_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Session recorded successfully');
    }

    public function clientHistory(User $user)
    {
        $trainer = Auth::user()->trainer;

        // Trainer logged sessions
        $trainerSessions = TrainerSession::where('user_id', $user->id)
            ->where('trainer_id', $trainer->id)
            ->get()
            ->map(function ($s) {
                $s->is_trainer_log = true;
                return $s;
            });

        // Student workout sessions
        $workoutSessions = \App\Models\WorkoutSession::where('user_id', $user->id)
            ->get()
            ->map(function ($s) {
                $s->is_trainer_log = false;
                return $s;
            });

        $history = $trainerSessions->concat($workoutSessions)->sortByDesc('created_at')->values();

        return response()->json(['history' => $history]);
    }

    public function manualBook(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_name' => 'nullable|string|max:255',
            'booking_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $trainer = Auth::user()->trainer;

        Booking::create([
            'trainer_id' => $trainer->id,
            'user_id' => $request->user_id,
            'course_name' => $request->course_name,
            'booking_date' => $request->booking_date,
            'status' => 'confirmed', // Manually added by trainer, so auto-confirm
            'notes' => $request->notes,
        ]);

        // Ensure package exists with correct hours from course if possible
        $course = null;
        if ($request->course_name) {
            $course = TrainerCourse::where('trainer_id', $trainer->id)
                ->where('title', $request->course_name)
                ->first();
        }
        $totalHours = $course ? $course->hours : 10;

        ClientPackage::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'trainer_id' => $trainer->id,
                'course_name' => $request->course_name,
            ],
            [
                'total_hours' => $totalHours,
                'used_hours' => 0,
                'status' => 'active',
            ]
        );

        return back()->with('success', 'Appointment scheduled successfully');
    }

    public function searchUsers(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json(['users' => $users]);
    }

    public function addClient(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_name' => 'nullable|string|max:255',
        ]);

        $trainer = Auth::user()->trainer;

        $totalHours = 10;
        if ($request->course_name) {
            $course = \App\Models\TrainerCourse::where('trainer_id', $trainer->id)
                ->where('title', $request->course_name)
                ->first();
            if ($course) {
                $totalHours = $course->hours;
            }
        }

        ClientPackage::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'trainer_id' => $trainer->id,
                'course_name' => $request->course_name,
            ],
            [
                'total_hours' => $totalHours,
                'status' => 'active',
            ]
        );

        return back()->with('success', 'Client added to roster successfully');
    }

    public function getSchedules()
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer)
            abort(403);

        $schedules = TrainerSchedule::where('trainer_id', $trainer->id)
            ->where('date', '>=', now()->subDays(30))
            ->where('date', '<=', now()->addDays(60))
            ->get();

        return response()->json(['schedules' => $schedules]);
    }

    public function updateSchedule(Request $request)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer)
            abort(403);

        $request->validate([
            'date' => 'required|date',
            'focus_area' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $schedule = TrainerSchedule::updateOrCreate(
            [
                'trainer_id' => $trainer->id,
                'date' => $request->date,
            ],
            [
                'focus_area' => $request->focus_area,
                'description' => $request->description,
            ]
        );

        return response()->json(['success' => true, 'schedule' => $schedule]);
    }

    public function deleteWorkoutSession(\App\Models\WorkoutSession $session)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer)
            abort(403);

        // Check if the session owner is a client of this trainer
        $isClient = ClientPackage::where('trainer_id', $trainer->id)
            ->where('user_id', $session->user_id)
            ->exists();

        if (!$isClient)
            abort(403);

        $session->delete();
        return back()->with('success', 'Workout session removed.');
    }

    public function deleteTrainerSession(TrainerSession $session)
    {
        $trainer = Auth::user()->trainer;
        if (!$trainer || $session->trainer_id !== $trainer->id) {
            abort(403);
        }

        $session->delete();
        return back()->with('success', 'Coaching session removed.');
    }
}
