<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard if logged in, or to login page if guest
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('queue.index')
        : redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Staff & Receptionist Queue Management
Route::middleware('auth')->prefix('antrian')->name('queue.')->group(function () {
    Route::get('/', [QueueController::class, 'index'])->name('index');
    Route::post('/manual', [QueueController::class, 'storeManual'])->name('store-manual');
    Route::post('/{queue}/call', [QueueController::class, 'call'])->name('call');
    Route::post('/{queue}/start', [QueueController::class, 'start'])->name('start');
    Route::post('/{queue}/finish', [QueueController::class, 'finish'])->name('finish');
    Route::post('/{queue}/skip', [QueueController::class, 'skip'])->name('skip');
    Route::post('/{queue}/cancel', [QueueController::class, 'cancel'])->name('cancel');
});

// Public Waiting Room Display (No login required)
Route::get('/display/{clinic_slug}', [QueueController::class, 'display'])->name('display');
