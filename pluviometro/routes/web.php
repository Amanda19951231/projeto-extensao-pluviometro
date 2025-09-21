<?php

use App\Http\Controllers\PluviometrosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', [WelcomeController::class, 'index']);

Route::get('/pluviometro', [PluviometrosController::class, 'pluviometros']);
Route::get('/pluviometros/dados', [PluviometrosController::class, 'dados'])->name('pluviometros.dados');
    
Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('/dashboard', function () {
    //     return Inertia::render('Dashboard');
    // })->name('dashboard');
    Route::get('/dashboard', [PluviometrosController::class, 'dashboard'])->name('dashboard');
    Route::get('/pluviometros', [PluviometrosController::class, 'index'])->name('pluviometros');
    Route::get('/pluviometros/create', [PluviometrosController::class, 'create'])->name('pluviometros.create');
    Route::post('/pluviometros', [PluviometrosController::class, 'store'])->name('pluviometros.store');
    Route::get('/pluviometros/{id}/edit', [PluviometrosController::class, 'edit'])->name('pluviometros.edit');
    Route::put('/pluviometros/{id}', [PluviometrosController::class, 'update'])->name('pluviometros.update');
    Route::delete('/pluviometros/{id}', [PluviometrosController::class, 'destroy'])->name('pluviometros.destroy');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
