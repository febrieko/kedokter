<?php

use App\Http\Controllers\Api\DisplayStreamController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// WhatsApp Webhook endpoint per clinic slug
Route::post('/webhook/whatsapp/{clinic_slug}', [WhatsAppWebhookController::class, 'handle']);

// SSE Stream for Waiting Room Display
Route::get('/display/{clinic_slug}/stream', [DisplayStreamController::class, 'stream']);
Route::get('/display/{clinic_slug}/data', [DisplayStreamController::class, 'data']);
