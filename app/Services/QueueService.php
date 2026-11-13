<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Holiday;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\ScheduleOverride;
use App\Models\ScheduleStat;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class QueueService
{
    /**
     * Get available dates for booking (Today up to 3 days ahead = H, H+1, H+2)
     */
    public function getAvailableBookingDates(int $daysAhead = 3): array
    {
        $dates = [];
        $start = Carbon::today();

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = $start->copy()->addDays($i);
            $dates[] = [
                'date' => $date->toDateString(),
                'formatted' => $date->locale('id')->translatedFormat('l, d M Y'),
                'weekday' => $date->dayOfWeek,
            ];
        }

        return $dates;
    }

    /**
     * Resolve schedule for a doctor on a specific date, checking overrides & holidays.
     */
    public function resolveSchedule(string $clinicId, string $doctorId, Carbon $targetDate): ?array
    {
        // 1. Check clinic holiday or doctor holiday on that date
        $isHoliday = Holiday::where('clinic_id', $clinicId)
            ->where('holiday_date', $targetDate->toDateString())
            ->where(function ($q) use ($doctorId) {
                $q->whereNull('doctor_id')->orWhere('doctor_id', $doctorId);
            })
            ->exists();

        if ($isHoliday) {
            return null; // Libur
        }

        // 2. Find weekly template schedule
        $schedule = DoctorSchedule::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->where('weekday', $targetDate->dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return null; // Tidak ada jadwal di hari tersebut
        }

        // 3. Check for specific date override
        $override = ScheduleOverride::where('doctor_schedule_id', $schedule->id)
            ->where('override_date', $targetDate->toDateString())
            ->first();

        if ($override && $override->is_cancelled) {
            return null; // Praktik dibatalkan pada tanggal ini
        }

        $startTime = $override?->start_time ?? $schedule->start_time;
        $endTime = $override?->end_time ?? $schedule->end_time;
        $quota = $override?->quota ?? $schedule->quota;

        // 4. Count current active queues
        $currentQueueCount = Queue::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->whereDate('queue_date', $targetDate)
            ->where('status', '!=', Queue::STATUS_CANCELLED)
            ->count();

        $remainingQuota = max(0, $quota - $currentQueueCount);

        return [
            'schedule_id' => $schedule->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'quota' => $quota,
            'booked' => $currentQueueCount,
            'remaining_quota' => $remainingQuota,
            'is_full' => $remainingQuota <= 0,
            'is_overridden' => $override !== null,
        ];
    }

    /**
     * Register a new queue atomically inside a database transaction.
     */
    public function registerQueue(
        string $clinicId,
        string $doctorId,
        string $patientId,
        string $targetDateString,
        string $source = Queue::SOURCE_WHATSAPP,
        ?int $userId = null,
        ?string $complaint = null
    ): Queue {
        $targetDate = Carbon::parse($targetDateString);
        $today = Carbon::today();
        $maxDate = $today->copy()->addDays(2); // H+2 (total 3 hari pendaftaran)

        if ($targetDate->lt($today) || $targetDate->gt($maxDate)) {
            throw new Exception("Pendaftaran hanya dibuka dari hari ini hingga 3 hari ke depan ({$today->format('d/m')} - {$maxDate->format('d/m')}).");
        }

        return DB::transaction(function () use ($clinicId, $doctorId, $patientId, $targetDate, $source, $userId, $complaint) {
            // Check if patient already has an active queue for this doctor on this date
            $existing = Queue::where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('patient_id', $patientId)
                ->whereDate('queue_date', $targetDate)
                ->where('status', '!=', Queue::STATUS_CANCELLED)
                ->first();

            if ($existing) {
                throw new Exception("Anda sudah terdaftar di antrian dokter ini dengan nomor {$existing->number}.");
            }

            // Resolve schedule & quota
            $scheduleInfo = $this->resolveSchedule($clinicId, $doctorId, $targetDate);
            if (!$scheduleInfo) {
                throw new Exception("Dokter tidak berpraktik pada tanggal {$targetDate->format('d/m/Y')}.");
            }

            if ($scheduleInfo['is_full']) {
                throw new Exception("Kuota antrian untuk dokter tersebut pada tanggal {$targetDate->format('d/m/Y')} sudah penuh.");
            }

            // Find next queue number atomically
            $maxNumber = Queue::where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->whereDate('queue_date', $targetDate)
                ->lockForUpdate()
                ->max('number') ?? 0;

            $nextNumber = $maxNumber + 1;

            return Queue::create([
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'doctor_schedule_id' => $scheduleInfo['schedule_id'],
                'patient_id' => $patientId,
                'queue_date' => $targetDate->toDateString(),
                'number' => $nextNumber,
                'status' => Queue::STATUS_WAITING,
                'complaint' => $complaint,
                'source' => $source,
                'call_count' => 0,
                'registered_at' => now(),
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Call next or current queue (up to 3 times before marked SKIPPED)
     */
    public function callQueue(Queue $queue): array
    {
        if (!$queue->canBeCalled()) {
            throw new Exception("Antrian berstatus {$queue->status} tidak dapat dipanggil.");
        }

        $newCallCount = $queue->call_count + 1;
        $status = Queue::STATUS_CALLED;

        if ($newCallCount > 3) {
            // Sudah 3 kali dipanggil dan belum hadir -> SKIPPED
            $queue->update([
                'status' => Queue::STATUS_SKIPPED,
                'call_count' => $newCallCount,
                'last_called_at' => now(),
            ]);

            return [
                'queue' => $queue,
                'action' => 'SKIPPED',
                'message' => "Nomor {$queue->number} telah dipanggil 3 kali dan ditandai Dilewati (SKIPPED).",
            ];
        }

        $queue->update([
            'status' => $status,
            'call_count' => $newCallCount,
            'called_at' => $queue->called_at ?? now(),
            'last_called_at' => now(),
        ]);

        return [
            'queue' => $queue,
            'action' => 'CALLED',
            'call_count' => $newCallCount,
            'message' => "Panggilan ke-{$newCallCount} untuk nomor antrian {$queue->number}.",
        ];
    }

    /**
     * Mark queue as in-service
     */
    public function startService(Queue $queue): Queue
    {
        $queue->update([
            'status' => Queue::STATUS_IN_SERVICE,
            'started_at' => now(),
        ]);

        return $queue;
    }

    /**
     * Mark queue as done & recalculate schedule stats
     */
    public function finishService(Queue $queue, ?string $notes = null): Queue
    {
        $finishedAt = now();
        $startedAt = $queue->started_at ?? $queue->called_at ?? $finishedAt;
        $serviceMinutes = max(1, (int) round($startedAt->diffInMinutes($finishedAt)));

        $updateData = [
            'status' => Queue::STATUS_DONE,
            'finished_at' => $finishedAt,
        ];

        if ($notes !== null) {
            $updateData['doctor_notes'] = $notes;
        }

        $queue->update($updateData);

        // Update statistics if schedule exists
        if ($queue->doctor_schedule_id) {
            $stat = ScheduleStat::firstOrNew([
                'clinic_id' => $queue->clinic_id,
                'doctor_schedule_id' => $queue->doctor_schedule_id,
                'stat_date' => $queue->queue_date,
            ]);

            $stat->patients_served = ($stat->patients_served ?? 0) + 1;
            $stat->total_service_minutes = ($stat->total_service_minutes ?? 0) + $serviceMinutes;
            $stat->avg_minutes = round($stat->total_service_minutes / $stat->patients_served, 2);
            $stat->save();
        }

        return $queue;
    }

    /**
     * Calculate estimated service time based on historical schedule statistics.
     */
    public function calculateEstimatedTime(Queue $queue): string
    {
        $schedule = $queue->schedule;
        $doctor = $queue->doctor;

        // 1. Get average minutes from stats or fallback to doctor's avg
        $avgMinutes = 15;
        if ($schedule) {
            $recentStat = ScheduleStat::where('doctor_schedule_id', $schedule->id)
                ->orderByDesc('stat_date')
                ->first();

            if ($recentStat && $recentStat->avg_minutes > 0) {
                $avgMinutes = (int) round($recentStat->avg_minutes);
            } elseif ($doctor && $doctor->avg_minutes_per_patient > 0) {
                $avgMinutes = $doctor->avg_minutes_per_patient;
            }
        }

        // 2. Count patients ahead who are still waiting / called
        $patientsAhead = Queue::where('clinic_id', $queue->clinic_id)
            ->where('doctor_id', $queue->doctor_id)
            ->whereDate('queue_date', $queue->queue_date)
            ->where('number', '<', $queue->number)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED, Queue::STATUS_IN_SERVICE])
            ->count();

        $startTimeStr = $schedule ? $schedule->start_time : '08:00:00';
        $baseTime = Carbon::parse($queue->queue_date->format('Y-m-d') . ' ' . $startTimeStr);

        // If today and schedule start has passed, calculate from now
        if ($queue->queue_date->isToday() && now()->gt($baseTime)) {
            $estimated = now()->addMinutes($patientsAhead * $avgMinutes);
        } else {
            $estimated = $baseTime->addMinutes(($queue->number - 1) * $avgMinutes);
        }

        return $estimated->format('H:i') . ' WIB';
    }
}
