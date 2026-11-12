<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaAdapter implements WhatsAppAdapterInterface
{
    public function sendMessage(string $phone, string $message, array $config = []): array
    {
        $baseUrl = rtrim($config['base_url'] ?? env('WAHA_BASE_URL', 'http://localhost:3000'), '/');
        $apiKey = $config['api_key'] ?? env('WAHA_API_KEY', '');
        $session = $config['session'] ?? 'default';

        // Clean phone number to digits only
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        $chatId = $digits . '@c.us';

        try {
            $client = Http::baseUrl($baseUrl);
            if (!empty($apiKey)) {
                $client->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $client->post('/api/sendText', [
                'session' => $session,
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'message_id' => $json['id'] ?? null,
                    'error' => null,
                ];
            }

            Log::error("[WahaAdapter] Error response: " . $response->body());
            return [
                'success' => false,
                'message_id' => null,
                'error' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("[WahaAdapter] Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function parseWebhook(Request $request): ?array
    {
        $event = $request->input('event');
        if ($event !== 'message') {
            return null;
        }

        $payload = $request->input('payload', []);
        $from = $payload['from'] ?? '';
        $body = trim((string) ($payload['body'] ?? ''));

        if (empty($from) || empty($body)) {
            return null;
        }

        // e.g. 6281234567890@c.us -> +6281234567890
        $digits = explode('@', $from)[0];
        $digits = preg_replace('/[^0-9]/', '', $digits);
        $formattedPhone = '+' . $digits;

        return [
            'sender' => $formattedPhone,
            'message' => $body,
            'message_id' => $payload['id'] ?? null,
            'raw' => $request->all(),
        ];
    }

    public function verifyWebhook(Request $request, array $config = []): bool
    {
        return true;
    }
}

