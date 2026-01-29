<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\GymLayoutController;

use App\Models\GymLayout;

Route::get('/', function () {
    return Inertia::render('Home', [
        'gyms' => GymLayout::where('is_public', true)->orWhere('id', '>', 0)->limit(6)->get() // For demo, fetch all or specific
    ]);
})->name('home');

Route::get('/gyms/{id}', [GymLayoutController::class, 'showPublic'])->name('gyms.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [GymLayoutController::class, 'index'])->name('dashboard');
    Route::resource('gym-builder', GymLayoutController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
