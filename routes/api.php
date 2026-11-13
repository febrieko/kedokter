<?php

use App\Http\Controllers\Api\DisplayStreamController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// WhatsApp Webhook endpoint per clinic slug
Route::post('/webhook/whatsapp/{clinic_slug}', [WhatsAppWebhookController::class, 'handle']);

// SSE Stream for Waiting Room Display
Route::get('/display/{clinic_slug}/stream', [DisplayStreamController::class, 'stream']);
Route::get('/display/{clinic_slug}/data', [DisplayStreamController::class, 'data']);

// ICD-10 Search Autocomplete for Doctors
Route::get('/icd10/search', [App\Http\Controllers\Api\Icd10ApiController::class, 'search'])->name('api.icd10.search');

