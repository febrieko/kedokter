<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisplayStreamController extends Controller
{
    /**
     * Get snapshot data of current active calls & queues for waiting room.
     */
    public function data(string $clinicSlug): JsonResponse
    {
        $clinic = Clinic::where('slug', $clinicSlug)->firstOrFail();
        return response()->json($this->getDisplayPayload($clinic));
    }

    /**
     * Server-Sent Events (SSE) stream for realtime waiting room display.
     */
    public function stream(string $clinicSlug): StreamedResponse
    {
        $clinic = Clinic::where('slug', $clinicSlug)->firstOrFail();

        return response()->stream(function () use ($clinic) {
            // Set headers for SSE
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $payload = $this->getDisplayPayload($clinic);
                echo "data: " . json_encode($payload) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Poll interval 2 seconds for SSE event delivery
                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function getDisplayPayload(Clinic $clinic): array
    {
        $today = Carbon::today()->toDateString();

        // 1. Current active called queue (most recently called)
        $currentCall = Queue::with(['doctor', 'patient'])
            ->where('clinic_id', $clinic->id)
            ->where('queue_date', $today)
            ->where('status', Queue::STATUS_CALLED)
            ->latest('last_called_at')
            ->first();

        // 2. Next 3 waiting queues
        $nextQueues = Queue::with('doctor')
            ->where('clinic_id', $clinic->id)
            ->where('queue_date', $today)
            ->where('status', Queue::STATUS_WAITING)
            ->orderBy('number')
            ->take(4)
            ->get()
            ->map(fn($q) => [
                'number' => $q->number,
                'doctor' => $q->doctor->name,
                'room' => $q->doctor->specialty,
            ]);

        // 3. Status summary per practicing doctor today
        $doctorQueues = Queue::with('doctor')
            ->where('clinic_id', $clinic->id)
            ->where('queue_date', $today)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED, Queue::STATUS_IN_SERVICE])
            ->get()
            ->groupBy('doctor_id')
            ->map(function ($items) {
                $doc = $items->first()->doctor;
                $calling = $items->firstWhere('status', Queue::STATUS_CALLED);
                $inService = $items->firstWhere('status', Queue::STATUS_IN_SERVICE);

                return [
                    'doctor_name' => $doc->name,
                    'specialty' => $doc->specialty,
                    'current_number' => $calling?->number ?? $inService?->number ?? '-',
                    'waiting_count' => $items->where('status', Queue::STATUS_WAITING)->count(),
                ];
            })->values();

        return [
            'clinic_name' => $clinic->name,
            'current_call' => $currentCall ? [
                'number' => $currentCall->number,
                'doctor' => $currentCall->doctor->name,
                'room' => $currentCall->doctor->specialty,
                'call_count' => $currentCall->call_count,
            ] : null,
            'next_queues' => $nextQueues,
            'doctor_queues' => $doctorQueues,
            'timestamp' => now()->format('H:i:s'),
        ];
    }
}
