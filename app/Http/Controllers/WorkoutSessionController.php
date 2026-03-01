<?php

namespace App\Http\Controllers;

use App\Models\WorkoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkoutSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = WorkoutSession::where('user_id', Auth::id())
            ->latest() // Order by created_at desc
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'workout_date' => 'required|date',
            'data' => 'required|array',
        ]);

        $session = WorkoutSession::create([
            'user_id' => Auth::id(),
            'workout_date' => $validated['workout_date'],
            'data' => $validated['data'],
        ]);

        return response()->json($session, 201);
    }

    public function destroy(WorkoutSession $workoutSession)
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        $workoutSession->delete();

        return response()->json(null, 204);
    }
}
