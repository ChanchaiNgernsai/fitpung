<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
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
            'location' => 'nullable|string',
            'google_map_url' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048', // 2MB Max
            'room_config' => 'present|array',
            'items' => 'present|array',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('gyms', 'public');
        }

        $layout = GymLayout::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'location' => $request->location,
            'google_map_url' => $request->google_map_url,
            'image_path' => $imagePath,
            'room_config' => $request->room_config,
            'items' => $request->items,
        ]);

        return redirect()->route('dashboard')->with('success', 'Layout saved successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GymLayout $gym_builder)
    {
        if ($gym_builder->user_id !== Auth::id()) {
            abort(403);
        }

        // Add image_url for the frontend
        $gym_builder->image_url = $gym_builder->image_path ? asset('storage/' . $gym_builder->image_path) : null;

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
            'location' => 'nullable|string',
            'google_map_url' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
            'room_config' => 'sometimes|present|array',
            'items' => 'sometimes|present|array',
            'operating_hours' => 'nullable|array',
            'holidays' => 'nullable|array',
            'promotions' => 'nullable|array',
            'price_list' => 'nullable|array',
        ]);

        $data = [
            'name' => $request->name,
            'location' => $request->location,
            'google_map_url' => $request->google_map_url,
            'operating_hours' => $request->operating_hours,
            'holidays' => $request->holidays,
            'promotions' => $request->promotions,
            'price_list' => $request->price_list,
        ];

        if ($request->has('room_config'))
            $data['room_config'] = $request->room_config;
        if ($request->has('items'))
            $data['items'] = $request->items;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($gym_builder->image_path && \Storage::disk('public')->exists($gym_builder->image_path)) {
                \Storage::disk('public')->delete($gym_builder->image_path);
            }
            $data['image_path'] = $request->file('image')->store('gyms', 'public');
        }

        $gym_builder->update($data);

        return redirect()->route('dashboard')->with('success', 'Layout updated successfully!');
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
    /**
     * Show the public gym details.
     */
    public function showPublic($id)
    {
        $gym = GymLayout::where('id', $id)->firstOrFail();

        // Allow guests to view the public gym page
        if (!$gym->is_public && $gym->user_id !== Auth::id() && !Auth::user()?->is_admin) {
            // Note: If you want guests to see ALL gyms regardless of is_public, remove this check.
            // But usually, we only want public ones visible to guests.
        }

        // Allow any logged-in user to view the gym
        // if (!$gym->is_public && $gym->user_id !== Auth::id()) {
        //     abort(404);
        // }

        return Inertia::render('Gyms/Show', [
            'gym' => $gym,
            'equipments' => Equipment::all()
        ]);
    }

    /**
     * Show the interactive map page.
     */
    public function showMap($id)
    {
        $gym = GymLayout::where('id', $id)->firstOrFail();

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return Inertia::render('Gyms/InteractiveMap', [
            'gym' => $gym,
            'equipments' => Equipment::all()
        ]);
    }
}
