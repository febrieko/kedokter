<?php

use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\WhatsAppSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SuperAdmin\ClinicController as SuperAdminClinicController;
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

// Protected Staff & Receptionist Queue Management (Scoped per Clinic)
Route::middleware(['auth', 'clinic.scope'])->prefix('antrian')->name('queue.')->group(function () {
    Route::get('/', [QueueController::class, 'index'])->name('index');
    Route::post('/manual', [QueueController::class, 'storeManual'])->name('store-manual');
    Route::post('/{queue}/call', [QueueController::class, 'call'])->name('call');
    Route::post('/{queue}/start', [QueueController::class, 'start'])->name('start');
    Route::post('/{queue}/finish', [QueueController::class, 'finish'])->name('finish');
    Route::post('/{queue}/skip', [QueueController::class, 'skip'])->name('skip');
    Route::post('/{queue}/cancel', [QueueController::class, 'cancel'])->name('cancel');
});

// Protected Clinic Administrator Master Data & Settings Panel (Scoped per Clinic)
Route::middleware(['auth', 'admin', 'clinic.scope'])->prefix('admin')->name('admin.')->group(function () {
    // 1. Doctors Management
    Route::get('/dokter', [DoctorController::class, 'index'])->name('doctors.index');
    Route::post('/dokter', [DoctorController::class, 'store'])->name('doctors.store');
    Route::put('/dokter/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
    Route::delete('/dokter/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');

    // 2. Weekly Schedules & Dynamic Overrides
    Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/jadwal', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/jadwal/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/jadwal/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::post('/jadwal/override', [ScheduleController::class, 'storeOverride'])->name('schedules.override.store');
    Route::delete('/jadwal/override/{override}', [ScheduleController::class, 'destroyOverride'])->name('schedules.override.destroy');

    // 3. Holidays
    Route::get('/libur', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/libur', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('/libur/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

    // 4. WhatsApp Gateway & Message Templates
    Route::get('/pengaturan-wa', [WhatsAppSettingController::class, 'index'])->name('whatsapp.index');
    Route::post('/pengaturan-wa/config', [WhatsAppSettingController::class, 'updateConfig'])->name('whatsapp.config');
    Route::post('/pengaturan-wa/test', [WhatsAppSettingController::class, 'testSend'])->name('whatsapp.test');
    Route::put('/pengaturan-wa/template/{template}', [WhatsAppSettingController::class, 'updateTemplate'])->name('whatsapp.template');
});

// Protected SuperAdmin Panel (Multi-Clinic Platform Management)
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/klinik', [SuperAdminClinicController::class, 'index'])->name('clinics.index');
    Route::post('/klinik', [SuperAdminClinicController::class, 'store'])->name('clinics.store');
    Route::put('/klinik/{clinic}', [SuperAdminClinicController::class, 'update'])->name('clinics.update');
    Route::post('/switch/{clinic}', [SuperAdminClinicController::class, 'switchClinic'])->name('clinics.switch');
});

// Public Waiting Room Display (No login required)
Route::get('/display/{clinic_slug}', [QueueController::class, 'display'])->name('display');
