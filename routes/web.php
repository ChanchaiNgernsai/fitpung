<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\GymLayoutController;
use App\Models\GymLayout;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TrainerController;

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

    // Trainer Registration
    Route::get('/trainer/register', [TrainerController::class, 'showRegistration'])->name('trainer.register');
    Route::post('/trainer/register', [TrainerController::class, 'storeRegistration'])->name('trainer.store');
    Route::get('/trainers', [TrainerController::class, 'index'])->name('trainers.index');
    Route::post('/api/trainer/book', [TrainerController::class, 'book'])->name('trainer.book');
    Route::post('/api/trainer/review', [TrainerController::class, 'storeReview'])->name('trainer.review');
    Route::post('/api/trainer/{trainer}/verify', [TrainerController::class, 'verify']);
    Route::delete('/api/trainer/verify/{verification}', [TrainerController::class, 'deleteVerification']);
    Route::get('/api/trainer/{trainer}/schedule', [TrainerController::class, 'getSchedule']);

    // Trainer Management
    Route::get('/mobile/trainer/dashboard', [\App\Http\Controllers\TrainerManagementController::class, 'index'])->name('trainer.dashboard');
    Route::patch('/api/bookings/{booking}', [\App\Http\Controllers\TrainerManagementController::class, 'updateBooking']);
    Route::delete('/api/bookings/{booking}', [\App\Http\Controllers\TrainerManagementController::class, 'deleteBooking']);
    Route::delete('/api/client-packages/{clientPackage}', [\App\Http\Controllers\TrainerManagementController::class, 'removeClient']);
    Route::get('/api/clients/{user}', [\App\Http\Controllers\TrainerManagementController::class, 'clientDetails']);
    Route::get('/api/clients/{user}/history', [\App\Http\Controllers\TrainerManagementController::class, 'clientHistory']);
    Route::get('/api/trainer/search-users', [\App\Http\Controllers\TrainerManagementController::class, 'searchUsers']);
    Route::post('/api/trainer/add-client', [\App\Http\Controllers\TrainerManagementController::class, 'addClient']);
    Route::post('/api/trainer/courses', [\App\Http\Controllers\TrainerManagementController::class, 'storeCourse'])->name('trainer.course.store');
    Route::patch('/api/trainer/courses/{course}', [\App\Http\Controllers\TrainerManagementController::class, 'updateCourse'])->name('trainer.course.update');
    Route::delete('/api/trainer/courses/{course}', [\App\Http\Controllers\TrainerManagementController::class, 'deleteCourse'])->name('trainer.course.delete');
    Route::post('/api/trainer/manual-book', [\App\Http\Controllers\TrainerManagementController::class, 'manualBook']);
    Route::post('/api/trainer/record-session', [\App\Http\Controllers\TrainerManagementController::class, 'recordSession']);
    Route::get('/api/trainer/schedules', [\App\Http\Controllers\TrainerManagementController::class, 'getSchedules']);
    Route::post('/api/trainer/schedules', [\App\Http\Controllers\TrainerManagementController::class, 'updateSchedule']);
});

require __DIR__ . '/auth.php';
