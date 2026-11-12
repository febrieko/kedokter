<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppManager;
use App\Services\WhatsAppBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppManager $waManager,
        protected WhatsAppBotService $botService
    ) {}

    /**
     * Handle incoming webhook for a specific clinic.
     */
    public function handle(Request $request, string $clinicSlug): JsonResponse
    {
        $clinic = Clinic::where('slug', $clinicSlug)->where('is_active', true)->first();

        if (!$clinic) {
            return response()->json(['error' => 'Klinik tidak ditemukan atau tidak aktif'], 404);
        }

        $provider = $clinic->whatsapp_provider ?: 'FONNTE';
        $adapter = $this->waManager->getAdapter($provider);

        // Verify webhook authenticity if needed
        if (!$adapter->verifyWebhook($request, $clinic->whatsapp_config ?? [])) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $incoming = $adapter->parseWebhook($request);

        if (!$incoming || empty($incoming['sender']) || empty($incoming['message'])) {
            // Not a message event or empty
            return response()->json(['status' => 'ignored']);
        }

        $phone = $incoming['sender'];
        $message = $incoming['message'];

        // Record incoming log
        MessageLog::create([
            'clinic_id' => $clinic->id,
            'phone' => $phone,
            'direction' => 'IN',
            'body' => $message,
            'status' => 'DELIVERED',
            'provider_message_id' => $incoming['message_id'] ?? null,
        ]);

        // Process message through bot state machine
        $reply = $this->botService->handleIncoming($clinic, $phone, $message);

        // Send reply back through WhatsApp
        if (!empty($reply)) {
            $this->waManager->send($clinic, $phone, $reply);
        }

        return response()->json(['status' => 'processed']);
    }
}
