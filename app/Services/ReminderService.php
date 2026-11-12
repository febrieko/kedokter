<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\MessageTemplate;
use App\Models\Queue;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    public function __construct(
        protected QueueService $queueService,
        protected WhatsAppManager $waManager
    ) {}

    /**
     * Send H-1 reminders to all patients scheduled for tomorrow.
     *
     * @param string|null $clinicId Optional specific clinic filter
     * @return array Summary of processed reminders
     */
    public function sendH1Reminders(?string $clinicId = null): array
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $query = Queue::with(['clinic', 'doctor', 'patient', 'schedule'])
            ->whereDate('queue_date', $tomorrow)
            ->where('status', Queue::STATUS_WAITING)
            ->whereNull('reminder_h1_sent_at');

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $queues = $query->get();
        $sentCount = 0;
        $failedCount = 0;

        foreach ($queues as $queue) {
            if (!$queue->patient || !$queue->patient->phone) {
                continue;
            }

            $clinic = $queue->clinic;
            if (!$clinic || !$clinic->is_active) {
                continue;
            }

            // Get template
            $template = MessageTemplate::where('clinic_id', $clinic->id)
                ->where('key', 'REMINDER_H1')
                ->first();

            $formattedDate = Carbon::parse($queue->queue_date)->locale('id')->translatedFormat('l, d F Y');
            $estTime = $this->queueService->calculateEstimatedTime($queue);

            $defaultMsg = "🔔 *PENGINGAT JADWAL DOKTER BESOK*\n\n"
                . "Halo *{$queue->patient->name}*,\n"
                . "Mengingatkan jadwal periksa Anda untuk *{$queue->doctor->name}* di *{$clinic->name}* besok ({$formattedDate}).\n\n"
                . "Nomor Antrian: *#{$queue->number}*\n"
                . "Estimasi Jam Pelayanan: *{$estTime}*\n\n"
                . "Harap hadir 15 menit sebelum estimasi waktu. Balas *CEK* untuk memantau status atau *BATAL* jika berhalangan.";

            $msg = $template ? $template->render([
                'nama' => $queue->patient->name,
                'nomor' => $queue->number,
                'dokter' => $queue->doctor->name,
                'klinik' => $clinic->name,
                'tanggal' => $formattedDate,
                'jam_estimasi' => $estTime,
            ]) : $defaultMsg;

            $result = $this->waManager->send($clinic, $queue->patient->phone, $msg, $queue->patient);

            if ($result['success']) {
                $queue->update(['reminder_h1_sent_at' => now()]);
                $sentCount++;
            } else {
                Log::warning("[ReminderService] Failed sending H-1 to {$queue->patient->phone}: " . ($result['error'] ?? 'Unknown error'));
                $failedCount++;
            }
        }

        return [
            'total_eligible' => $queues->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
        ];
    }

    /**
     * Check and trigger Near-Turn reminder (sisa 2 antrian di depan).
     *
     * @param Queue $currentQueue Currently called or active queue
     * @return Queue|null The queue notified, or null if none
     */
    public function checkAndTriggerNearTurn(Queue $currentQueue): ?Queue
    {
        $clinic = $currentQueue->clinic;
        if (!$clinic || !$clinic->is_active) {
            return null;
        }

        // Find upcoming waiting queues behind current number
        $upcomingWaitingQueues = Queue::with(['patient', 'doctor', 'clinic'])
            ->where('clinic_id', $currentQueue->clinic_id)
            ->where('doctor_id', $currentQueue->doctor_id)
            ->whereDate('queue_date', $currentQueue->queue_date)
            ->where('number', '>', $currentQueue->number)
            ->where('status', Queue::STATUS_WAITING)
            ->whereNull('near_turn_sent_at')
            ->orderBy('number')
            ->take(2)
            ->get();

        // The target patient is the one at index 1 (the 2nd patient in line, meaning 2 patients ahead)
        // If there's only 1 patient waiting, they might also be alerted if desired
        $targetQueue = $upcomingWaitingQueues->count() >= 2
            ? $upcomingWaitingQueues[1]
            : $upcomingWaitingQueues->first();

        if (!$targetQueue || !$targetQueue->patient || !$targetQueue->patient->phone) {
            return null;
        }

        // Calculate patients ahead
        $patientsAhead = Queue::where('clinic_id', $targetQueue->clinic_id)
            ->where('doctor_id', $targetQueue->doctor_id)
            ->whereDate('queue_date', $targetQueue->queue_date)
            ->where('number', '<', $targetQueue->number)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED, Queue::STATUS_IN_SERVICE])
            ->count();

        // Template NEAR_TURN
        $template = MessageTemplate::where('clinic_id', $clinic->id)
            ->where('key', 'NEAR_TURN')
            ->first();

        $defaultMsg = "⏳ *GILIRAN ANDA SEMAKIN DEKAT*\n\n"
            . "Halo *{$targetQueue->patient->name}*,\n"
            . "Tersisa *{$patientsAhead} pasien* di depan nomor antrian Anda (*#{$targetQueue->number}*) untuk *{$targetQueue->doctor->name}*.\n\n"
            . "Silakan segera bersiap di ruang tunggu klinik agar tidak terlewat saat nomor Anda dipanggil.";

        $msg = $template ? $template->render([
            'nama' => $targetQueue->patient->name,
            'nomor' => $targetQueue->number,
            'dokter' => $targetQueue->doctor->name,
            'klinik' => $clinic->name,
            'sisa_antrian' => $patientsAhead,
        ]) : $defaultMsg;

        $result = $this->waManager->send($clinic, $targetQueue->patient->phone, $msg, $targetQueue->patient);

        if ($result['success']) {
            $targetQueue->update(['near_turn_sent_at' => now()]);
            return $targetQueue;
        }

        return null;
    }
}

