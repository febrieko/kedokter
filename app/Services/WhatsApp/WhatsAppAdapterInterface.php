<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;

interface WhatsAppAdapterInterface
{
    /**
     * Send a text message to a WhatsApp number.
     *
     * @param string $phone E.164 format or 08xx
     * @param string $message
     * @param array $config Clinic-specific whatsapp config (token, sender, etc.)
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function sendMessage(string $phone, string $message, array $config = []): array;

    /**
     * Parse incoming webhook payload into a normalized structure:
     * ['sender' => '...', 'message' => '...', 'message_id' => '...']
     */
    public function parseWebhook(Request $request): ?array;

    /**
     * Verify webhook signature/token authenticity.
     */
    public function verifyWebhook(Request $request, array $config = []): bool;
}
