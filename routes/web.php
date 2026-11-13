<?php

use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\WhatsAppSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Doctor\DoctorQueueController;
use App\Http\Controllers\Doctor\MedicalRecordController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SuperAdmin\ClinicController as SuperAdminClinicController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard if logged in, or to login page if guest
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    if (Auth::user()->isDoctor()) {
        return redirect()->route('doctor.queue.index');
    }
    return redirect()->route('queue.index');
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
    Route::post('/reminders/h1', [QueueController::class, 'triggerH1Reminders'])->name('reminders.h1');
});

// Protected Doctor Examination Portal (Scoped per Clinic, restricted to Doctor & Admin)
Route::middleware(['auth', 'doctor.or.admin', 'clinic.scope'])->prefix('dokter')->name('doctor.')->group(function () {
    Route::get('/antrian', [DoctorQueueController::class, 'index'])->name('queue.index');
    Route::post('/antrian/panggil-berikutnya', [DoctorQueueController::class, 'callNext'])->name('queue.call-next');
    Route::post('/antrian/{queue}/call', [DoctorQueueController::class, 'call'])->name('queue.call');
    Route::post('/antrian/{queue}/start', [DoctorQueueController::class, 'start'])->name('queue.start');
    Route::post('/antrian/{queue}/finish', [DoctorQueueController::class, 'finish'])->name('queue.finish');
    Route::post('/antrian/{queue}/skip', [DoctorQueueController::class, 'skip'])->name('queue.skip');
    Route::post('/antrian/{queue}/recall', [DoctorQueueController::class, 'recall'])->name('queue.recall');

    // Rekam Medis Elektronik (RME) Detail, Cetak, Kunci (Lock/Sign), & Addendum
    Route::get('/rekam-medis/{encounter}', [MedicalRecordController::class, 'show'])->name('medical-record.show');
    Route::get('/rekam-medis/{encounter}/cetak', [MedicalRecordController::class, 'print'])->name('medical-record.print');
    Route::post('/rekam-medis/{encounter}/lock', [MedicalRecordController::class, 'lock'])->name('medical-record.lock');
    Route::post('/rekam-medis/{encounter}/addendum', [MedicalRecordController::class, 'addAddendum'])->name('medical-record.addendum');
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
