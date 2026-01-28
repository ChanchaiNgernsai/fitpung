<?php

namespace App\Http\Controllers;

use App\Models\GymLayout;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class GymLayoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $layouts = GymLayout::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Dashboard', [
            'layouts' => $layouts
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('GymBuilder', [
            'layout' => null
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'room_config' => 'required|array',
            'items' => 'required|array',
        ]);

        $layout = GymLayout::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'room_config' => $request->room_config,
            'items' => $request->items,
        ]);

        return redirect()->route('gym-builder.edit', $layout->id)->with('success', 'Layout saved successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GymLayout $gym_builder)
    {
        // $gym_builder is the route param name if we stick to resource naming Convention, 
        // or we can name it explicitly.
        // Assuming route is /gym-builder/{id}/edit or just /gym-builder/{id}

        if ($gym_builder->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('GymBuilder', [
            'layout' => $gym_builder
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GymLayout $gym_builder)
    {
        if ($gym_builder->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'room_config' => 'required|array',
            'items' => 'required|array',
        ]);

        $gym_builder->update([
            'name' => $request->name,
            'room_config' => $request->room_config,
            'items' => $request->items,
        ]);

        return redirect()->back()->with('success', 'Layout updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GymLayout $gym_builder)
    {
        if ($gym_builder->user_id !== Auth::id()) {
            abort(403);
        }

        $gym_builder->delete();

        return redirect()->route('dashboard')->with('success', 'Layout deleted.');
    }
}
