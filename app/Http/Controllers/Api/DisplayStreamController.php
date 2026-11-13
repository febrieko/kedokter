<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisplayStreamController extends Controller
{
    /**
     * Get snapshot data of current active calls & queues for waiting room.
     */
    public function data(Request $request, string $clinicSlug): JsonResponse
    {
        $clinic = Clinic::where('slug', $clinicSlug)->firstOrFail();
        $date = $request->input('date', Carbon::today()->toDateString());
        $doctorId = $request->input('doctor_id');

        return response()->json($this->getDisplayPayload($clinic, $date, $doctorId));
    }

    /**
     * Server-Sent Events (SSE) stream for realtime waiting room display.
     */
    public function stream(Request $request, string $clinicSlug): StreamedResponse
    {
        $clinic = Clinic::where('slug', $clinicSlug)->firstOrFail();
        $date = $request->input('date', Carbon::today()->toDateString());
        $doctorId = $request->input('doctor_id');

        return response()->stream(function () use ($clinic, $date, $doctorId) {
            // Run loop for max 25 iterations (50 seconds) so PHP workers in Apache/Laragon cleanly reconnect
            for ($i = 0; $i < 25; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $payload = $this->getDisplayPayload($clinic, $date, $doctorId);
                echo "data: " . json_encode($payload) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function getDisplayPayload(Clinic $clinic, ?string $targetDate = null, ?string $doctorId = null): array
    {
        $date = $targetDate ?: Carbon::today()->toDateString();

        // 1. Ambil seluruh dokter aktif di klinik beserta ruang praktiknya
        $doctorsInClinic = $clinic->doctors()->where('clinic_doctors.is_active', true)->get()->keyBy('id');

        // 2. Pasien Aktif (Prioritas: Sedang Dipanggil CALLED terbaru, atau Sedang Dilayani IN_SERVICE)
        $currentCallQuery = Queue::with(['doctor', 'patient'])
            ->where('clinic_id', $clinic->id)
            ->whereDate('queue_date', $date)
            ->whereIn('status', [Queue::STATUS_CALLED, Queue::STATUS_IN_SERVICE]);

        if ($doctorId) {
            $currentCallQuery->where('doctor_id', $doctorId);
        }

        // Urutkan prioritas: CALLED lebih utama dari IN_SERVICE, lalu berdasarkan waktu panggilan terakhir
        $currentCall = $currentCallQuery
            ->orderByRaw("CASE WHEN status = 'CALLED' THEN 1 ELSE 2 END")
            ->latest('last_called_at')
            ->latest('started_at')
            ->first();

        $currentCallData = null;
        if ($currentCall) {
            $docPivot = $doctorsInClinic->get($currentCall->doctor_id);
            $roomName = $docPivot?->pivot->room_number ?? 'Ruang Poli';

            $currentCallData = [
                'id' => $currentCall->id,
                'number' => $currentCall->number,
                'status' => $currentCall->status,
                'status_label' => $currentCall->status === Queue::STATUS_IN_SERVICE ? 'Sedang Dilayani' : 'Sedang Dipanggil',
                'doctor' => $currentCall->doctor->name,
                'specialty' => $currentCall->doctor->specialty,
                'room' => $roomName,
                'call_count' => $currentCall->call_count,
                'patient_name' => $currentCall->patient->name,
                'last_called_at' => $currentCall->last_called_at?->toIso8601String(),
            ];
        }

        // 3. Antrian Menunggu Berikutnya (Next In Line)
        $nextQueuesQuery = Queue::with(['doctor', 'patient'])
            ->where('clinic_id', $clinic->id)
            ->whereDate('queue_date', $date)
            ->where('status', Queue::STATUS_WAITING)
            ->orderBy('number');

        if ($doctorId) {
            $nextQueuesQuery->where('doctor_id', $doctorId);
        }

        $nextQueues = $nextQueuesQuery->take(4)->get()->map(function ($q) use ($doctorsInClinic) {
            $docPivot = $doctorsInClinic->get($q->doctor_id);
            $roomName = $docPivot?->pivot->room_number ?? 'Ruang Poli';

            return [
                'number' => $q->number,
                'doctor' => $q->doctor->name,
                'specialty' => $q->doctor->specialty,
                'room' => $roomName,
                'patient_name' => $q->patient->name,
            ];
        });

        // 4. Ringkasan Status per Dokter Praktik di Hari Ini
        $doctorQueues = $doctorsInClinic->map(function ($doc) use ($clinic, $date) {
            $docQueues = Queue::where('clinic_id', $clinic->id)
                ->where('doctor_id', $doc->id)
                ->whereDate('queue_date', $date)
                ->get();

            $calling = $docQueues->firstWhere('status', Queue::STATUS_CALLED);
            $inService = $docQueues->firstWhere('status', Queue::STATUS_IN_SERVICE);
            $waitingCount = $docQueues->where('status', Queue::STATUS_WAITING)->count();
            $doneCount = $docQueues->where('status', Queue::STATUS_DONE)->count();

            $statusText = '-';
            if ($calling) {
                $statusText = "#{$calling->number} (Dipanggil)";
            } elseif ($inService) {
                $statusText = "#{$inService->number} (Diperiksa)";
            } elseif ($doneCount > 0) {
                $statusText = "Selesai ({$doneCount})";
            }

            return [
                'doctor_id' => $doc->id,
                'doctor_name' => $doc->name,
                'specialty' => $doc->specialty,
                'room' => $doc->pivot->room_number ?? 'Ruang Poli',
                'current_number' => $statusText,
                'waiting_count' => $waitingCount,
                'done_count' => $doneCount,
            ];
        })->values();

        return [
            'clinic_name' => $clinic->name,
            'date' => $date,
            'formatted_date' => Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y'),
            'current_call' => $currentCallData,
            'next_queues' => $nextQueues,
            'doctor_queues' => $doctorQueues,
            'timestamp' => now()->format('H:i:s'),
        ];
    }
}
