<?php

namespace App\Http\Controllers;

use App\Models\WorkoutPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkoutPlanController extends Controller
{
    public function index()
    {
        return WorkoutPlan::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'data' => 'required|array',
        ]);

        $plan = WorkoutPlan::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'data' => $request->data,
        ]);

        return response()->json($plan);
    }

    public function destroy(WorkoutPlan $workoutPlan)
    {
        if ($workoutPlan->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workoutPlan->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
