<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\GymLayoutController;

use App\Models\GymLayout;

use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return Inertia::render('Home', [
        'gyms' => GymLayout::where('is_public', true)
            ->where('is_approved', true) // Only show approved gyms
            ->limit(6)
            ->get()
    ]);
})->name('home');

Route::get('/gyms/{id}', [GymLayoutController::class, 'showPublic'])->name('gyms.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [GymLayoutController::class, 'index'])->name('dashboard');
    Route::resource('gym-builder', GymLayoutController::class);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/gym/{id}/approve', [AdminController::class, 'approve'])->name('admin.gym.approve');
    Route::post('/admin/gym/{id}/reject', [AdminController::class, 'reject'])->name('admin.gym.reject');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
