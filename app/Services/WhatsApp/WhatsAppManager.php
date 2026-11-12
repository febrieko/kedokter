<?php

namespace App\Services\WhatsApp;

use App\Models\Clinic;
use App\Models\MessageLog;
use App\Models\Patient;
use InvalidArgumentException;

class WhatsAppManager
{
    protected array $adapters = [];

    public function getAdapter(string $provider): WhatsAppAdapterInterface
    {
        $provider = strtoupper($provider);

        if (isset($this->adapters[$provider])) {
            return $this->adapters[$provider];
        }

        return $this->adapters[$provider] = match ($provider) {
            'FONNTE' => new FonnteAdapter(),
            'WAHA' => new WahaAdapter(),
            default => throw new InvalidArgumentException("WhatsApp provider [{$provider}] tidak didukung."),
        };
    }

    /**
     * Send message to a patient/phone via a clinic's configured WhatsApp provider.
     */
    public function send(Clinic $clinic, string $phone, string $message, ?Patient $patient = null): array
    {
        $provider = $clinic->whatsapp_provider ?: 'FONNTE';
        $adapter = $this->getAdapter($provider);
        $config = $clinic->whatsapp_config ?? [];

        $result = $adapter->sendMessage($phone, $message, $config);

        // Record message log
        MessageLog::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient?->id,
            'phone' => $phone,
            'direction' => 'OUT',
            'body' => $message,
            'status' => $result['success'] ? 'SENT' : 'FAILED',
            'provider_message_id' => $result['message_id'] ?? null,
        ]);

        return $result;
    }
}

