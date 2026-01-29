<?php

namespace App\Http\Controllers;

use App\Models\GymLayout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $gyms = GymLayout::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/AdminDashboard', [
            'gyms' => $gyms
        ]);
    }

    public function approve($id)
    {
        $gym = GymLayout::findOrFail($id);
        $gym->update([
            'is_approved' => true,
            'is_public' => true
        ]);

        return back();
    }

    public function reject($id)
    {
        $gym = GymLayout::findOrFail($id);
        $gym->update(['is_approved' => false]);

        return back();
    }
}
