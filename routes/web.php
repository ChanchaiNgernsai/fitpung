<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\GymLayoutController;
use App\Models\GymLayout;
use App\Http\Controllers\AdminController;

Route::get('/', [GymLayoutController::class, 'showSelection'])->name('selection');
Route::get('/home', [GymLayoutController::class, 'showHome'])->name('home');

Route::get('/gyms/{id}', [GymLayoutController::class, 'showPublic'])->name('gyms.show');
Route::get('/gyms/{id}/map', [GymLayoutController::class, 'showMap'])->name('gyms.map');
Route::get('/gyms/{id}/technique', [GymLayoutController::class, 'showTechnique'])->name('gyms.technique');
Route::get('/gyms/{id}/white-map', [GymLayoutController::class, 'showWhiteMap'])->name('gyms.white-map');
Route::get('/gyms/{id}/recommend', [GymLayoutController::class, 'showRecommendations'])->name('gyms.recommend');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [GymLayoutController::class, 'index'])->name('dashboard');
    Route::post('/gym-builder/{gym_builder}', [GymLayoutController::class, 'update'])->name('gym-builder.post_update');
    Route::resource('gym-builder', GymLayoutController::class);

    // Workouts
    Route::get('/workouts', function () {
        return Inertia::render('Workouts/Index');
    })->name('workouts.index');

    Route::get('/workouts/run', function () {
        return Inertia::render('Workouts/Run', [
            'plan' => request()->query('plan')
        ]);
    })->name('workouts.run');

    Route::post('/workouts/run', function () {
        return Inertia::render('Workouts/Run', [
            'plan' => request()->input('plan')
        ]);
    });
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

    // Mobile Routes
    Route::get('/mobile/home', [GymLayoutController::class, 'showMobileHome'])->name('mobile.home');
    Route::get('/mobile/maps', [GymLayoutController::class, 'showMobileMaps'])->name('mobile.maps');
    Route::get('/mobile/workout', [GymLayoutController::class, 'showMobileWorkout'])->name('mobile.workout');
    Route::get('/mobile/stats', [GymLayoutController::class, 'showMobileStats'])->name('mobile.stats');
    Route::get('/mobile/profile', [GymLayoutController::class, 'showMobileProfile'])->name('mobile.profile');
    Route::get('/mobile/settings', [GymLayoutController::class, 'showMobileSettings'])->name('mobile.settings');

    // Workout Plans (Mobile API-like)
    Route::get('/api/workout-plans', [\App\Http\Controllers\WorkoutPlanController::class, 'index']);
    Route::post('/api/workout-plans', [\App\Http\Controllers\WorkoutPlanController::class, 'store']);
    Route::delete('/api/workout-plans/{workoutPlan}', [\App\Http\Controllers\WorkoutPlanController::class, 'destroy']);

    // Workout Sessions
    Route::get('/api/workout-sessions', [\App\Http\Controllers\WorkoutSessionController::class, 'index']);
    Route::post('/api/workout-sessions', [\App\Http\Controllers\WorkoutSessionController::class, 'store']);
    Route::delete('/api/workout-sessions/{workoutSession}', [\App\Http\Controllers\WorkoutSessionController::class, 'destroy']);
});

require __DIR__ . '/auth.php';
