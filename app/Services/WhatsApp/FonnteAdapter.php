<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteAdapter implements WhatsAppAdapterInterface
{
    protected string $defaultToken;

    public function __construct()
    {
        $this->defaultToken = config('services.fonnte.token', env('FONNTE_TOKEN', ''));
    }

    public function sendMessage(string $phone, string $message, array $config = []): array
    {
        $token = $config['token'] ?? $this->defaultToken;

        if (empty($token)) {
            Log::warning("[FonnteAdapter] Token is empty. Message not sent: {$phone}");
            return ['success' => false, 'message_id' => null, 'error' => 'Token Fonnte belum dikonfigurasi'];
        }

        // Clean phone number (e.g. +62812... -> 0812... or 62812...)
        $target = ltrim(preg_replace('/[^0-9]/', '', $phone), '+');

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? false)) {
                return [
                    'success' => true,
                    'message_id' => $json['id'] ?? null,
                    'error' => null,
                ];
            }

            Log::error("[FonnteAdapter] Error response: " . $response->body());
            return [
                'success' => false,
                'message_id' => null,
                'error' => $json['reason'] ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("[FonnteAdapter] Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function parseWebhook(Request $request): ?array
    {
        $sender = $request->input('sender');
        $message = trim((string) $request->input('message'));

        if (!$sender || $message === '') {
            return null;
        }

        // Normalize phone to format +628...
        $cleanPhone = preg_replace('/[^0-9]/', '', $sender);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
        $formattedPhone = '+' . $cleanPhone;

        return [
            'sender' => $formattedPhone,
            'message' => $message,
            'message_id' => $request->input('id'),
            'raw' => $request->all(),
        ];
    }

    public function verifyWebhook(Request $request, array $config = []): bool
    {
        // Fonnte can pass a verification token or header if configured
        return true;
    }
}
