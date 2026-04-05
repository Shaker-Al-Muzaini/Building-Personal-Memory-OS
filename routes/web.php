<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\MoneyController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FocusController;
use App\Http\Controllers\HealthController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/generate-plan', [DashboardController::class, 'generatePlan'])->name('dashboard.generate-plan');
    Route::get('/dashboard/search', [DashboardController::class, 'search'])->name('dashboard.search');
    Route::post('/dashboard/command', [DashboardController::class, 'handleCommand'])->name('dashboard.command');
    
  
    Route::get('/focus', [FocusController::class, 'index'])->name('focus.index');
    Route::post('/focus/plan', [FocusController::class, 'generateFocusPlan'])->name('focus.plan');
    
    // Health Route
    Route::get('/health', [HealthController::class, 'index'])->name('health.index');
    Route::post('/health', [HealthController::class, 'store'])->name('health.store');
    Route::post('/health/analyze', [HealthController::class, 'analyze'])->name('health.analyze');
    Route::post('/dashboard/tasks', [DashboardController::class, 'storeTask'])->name('tasks.store');
    Route::patch('/dashboard/tasks/{id}', [DashboardController::class, 'toggleTask'])->name('tasks.toggle');
    Route::post('/dashboard/habits', [DashboardController::class, 'storeHabit'])->name('habits.store');
    Route::post('/dashboard/goals', [DashboardController::class, 'storeGoal'])->name('goals.store');

    
    Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
    Route::post('/people', [PeopleController::class, 'store'])->name('people.store');
    Route::patch('/people/{id}/touch', [PeopleController::class, 'touch'])->name('people.touch');
    Route::delete('/people/{id}', [PeopleController::class, 'deleteAction'])->name('people.delete');
    Route::post('/people/generate-plan', [PeopleController::class, 'generatePeoplePlan'])->name('people.generate-plan');
    Route::get('/people/{id}/advice', [PeopleController::class, 'quickAdvice'])->name('people.advice');

    
    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::patch('/ideas/{id}/status', [IdeaController::class, 'updateStatus'])->name('ideas.status');
    Route::delete('/ideas/{id}', [IdeaController::class, 'destroy'])->name('ideas.delete');

    
    Route::get('/decisions', [DecisionController::class, 'index'])->name('decisions.index');
    Route::post('/decisions', [DecisionController::class, 'store'])->name('decisions.store');
    Route::patch('/decisions/{id}', [DecisionController::class, 'finalize'])->name('decisions.finalize');
    Route::delete('/decisions/{id}', [DecisionController::class, 'destroy'])->name('decisions.delete');

   
    Route::get('/money', [MoneyController::class, 'index'])->name('money.index');
    Route::post('/money', [MoneyController::class, 'store'])->name('money.store');
    Route::delete('/money/{id}', [MoneyController::class, 'destroy'])->name('money.delete');
    Route::post('/money/analyze', [MoneyController::class, 'analyze'])->name('money.analyze');
    Route::get('/money/forecast', [MoneyController::class, 'forecast'])->name('money.forecast');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
