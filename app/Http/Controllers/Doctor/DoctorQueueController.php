<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Queue;
use App\Models\ScheduleStat;
use App\Services\QueueService;
use App\Services\ReminderService;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorQueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected WhatsAppManager $waManager,
        protected ReminderService $reminderService
    ) {}

    /**
     * Tampilan Antrian Ruang Praktik Dokter
     */
    public function index(Request $request): View|RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        $user = $request->user();
        $date = $request->input('date', Carbon::today()->toDateString());

        // Daftar dokter yang aktif di klinik ini
        $doctors = $clinic->doctors()->where('clinic_doctors.is_active', true)->get();

        if ($doctors->isEmpty()) {
            return redirect()->route('queue.index')->with('warning', 'Belum ada dokter aktif yang terdaftar di klinik ini.');
        }

        // Resolusi dokter yang sedang login atau yang dipilih
        if ($user->isDoctor() && $user->doctor_id) {
            $selectedDoctor = $doctors->firstWhere('id', $user->doctor_id) ?? Doctor::find($user->doctor_id);
        } else {
            $doctorId = $request->input('doctor_id', $doctors->first()->id);
            $selectedDoctor = $doctors->firstWhere('id', $doctorId) ?? $doctors->first();
        }

        if (!$selectedDoctor) {
            return redirect()->route('queue.index')->with('warning', 'Dokter tidak ditemukan.');
        }

        // Ambil semua antrian hari ini untuk dokter ini
        $allQueues = Queue::with(['patient', 'schedule'])
            ->where('clinic_id', $clinic->id)
            ->where('doctor_id', $selectedDoctor->id)
            ->whereDate('queue_date', $date)
            ->orderBy('number')
            ->get();

        // 1. Pasien Aktif (Sedang diperiksa atau baru saja dipanggil)
        $activeQueue = $allQueues->firstWhere('status', Queue::STATUS_IN_SERVICE)
            ?? $allQueues->firstWhere('status', Queue::STATUS_CALLED);

        // 2. Daftar Menunggu (WAITING)
        $waitingQueues = $allQueues->where('status', Queue::STATUS_WAITING)->values();
        $nextQueue = $waitingQueues->first();

        // 3. Selesai (DONE)
        $doneQueues = $allQueues->where('status', Queue::STATUS_DONE)->sortByDesc('finished_at')->values();

        // 4. Dilewati (SKIPPED)
        $skippedQueues = $allQueues->where('status', Queue::STATUS_SKIPPED)->values();

        // Statistik
        $totalCount = $allQueues->where('status', '!=', Queue::STATUS_CANCELLED)->count();
        $doneCount = $doneQueues->count();
        $waitingCount = $waitingQueues->count();
        $skippedCount = $skippedQueues->count();

        // Hitung estimasi rata-rata waktu per pasien
        $avgDuration = $selectedDoctor->avg_minutes_per_patient ?? 15;
        $stat = ScheduleStat::where('clinic_id', $clinic->id)
            ->whereDate('stat_date', $date)
            ->first();
        if ($stat && $stat->avg_minutes > 0) {
            $avgDuration = round($stat->avg_minutes);
        }

        $scheduleInfo = $this->queueService->resolveSchedule($clinic->id, $selectedDoctor->id, Carbon::parse($date));

        return view('doctor.queue', compact(
            'clinic',
            'doctors',
            'selectedDoctor',
            'date',
            'activeQueue',
            'nextQueue',
            'waitingQueues',
            'doneQueues',
            'skippedQueues',
            'totalCount',
            'doneCount',
            'waitingCount',
            'skippedCount',
            'avgDuration',
            'scheduleInfo'
        ));
    }

    /**
     * Panggil antrian berikutnya secara otomatis
     */
    public function callNext(Request $request): RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        $doctorId = $request->input('doctor_id', $request->user()?->doctor_id);
        $date = Carbon::today()->toDateString();

        $nextQueue = Queue::where('clinic_id', $clinic->id)
            ->where('doctor_id', $doctorId)
            ->whereDate('queue_date', $date)
            ->where('status', Queue::STATUS_WAITING)
            ->orderBy('number')
            ->first();

        if (!$nextQueue) {
            return back()->with('info', 'Tidak ada pasien berikutnya yang sedang menunggu.');
        }

        return $this->processCall($nextQueue);
    }

    /**
     * Panggil nomor antrian tertentu
     */
    public function call(Queue $queue): RedirectResponse
    {
        return $this->processCall($queue);
    }

    /**
     * Mulai pelayanan / pemeriksaan pasien
     */
    public function start(Queue $queue): RedirectResponse
    {
        $this->queueService->startService($queue);
        return back()->with('success', "Pemeriksaan pasien #{$queue->number} ({$queue->patient->name}) telah dimulai.");
    }

    /**
     * Selesai pemeriksaan dengan opsional catatan dokter / resep / diagnosa
     */
    public function finish(Request $request, Queue $queue): RedirectResponse
    {
        $notes = $request->input('doctor_notes');
        $this->queueService->finishService($queue, $notes);

        // Otomasi Pengingat: Pemicu pengingat giliran dekat bagi pasien berikutnya
        $this->reminderService->checkAndTriggerNearTurn($queue);

        return back()->with('success', "Pemeriksaan pasien #{$queue->number} ({$queue->patient->name}) selesai. Catatan berhasil disimpan.");
    }

    /**
     * Lewati pasien jika tidak hadir setelah dipanggil
     */
    public function skip(Queue $queue): RedirectResponse
    {
        $queue->update([
            'status' => Queue::STATUS_SKIPPED,
            'last_called_at' => now(),
        ]);

        return back()->with('warning', "Pasien #{$queue->number} ({$queue->patient->name}) ditandai dilewati (SKIPPED).");
    }

    /**
     * Panggil kembali pasien yang sebelumnya dilewati (SKIPPED)
     */
    public function recall(Queue $queue): RedirectResponse
    {
        $queue->update([
            'status' => Queue::STATUS_CALLED,
            'called_at' => $queue->called_at ?? now(),
            'last_called_at' => now(),
        ]);

        // Panggil dan kirim notifikasi
        if ($queue->patient && $queue->patient->phone) {
            $msg = "📢 *PANGGILAN KEMBALI ANTRIAN #{$queue->number}*\n\n"
                . "Halo {$queue->patient->name},\n"
                . "Nomor antrian Anda *#{$queue->number}* untuk *{$queue->doctor->name}* kini dipanggil kembali.\n\n"
                . "Silakan langsung menuju ke ruang periksa sekarang. Terima kasih!";

            $this->waManager->send($queue->clinic, $queue->patient->phone, $msg, $queue->patient);
        }

        return back()->with('success', "Pasien #{$queue->number} ({$queue->patient->name}) dipanggil kembali ke ruang periksa.");
    }

    /**
     * Helper eksekusi pemanggilan antrian
     */
    protected function processCall(Queue $queue): RedirectResponse
    {
        try {
            $result = $this->queueService->callQueue($queue);

            // Kirim WhatsApp jika pasien memiliki nomor telepon
            if ($queue->patient && $queue->patient->phone) {
                $callCount = $result['call_count'] ?? 1;
                $msg = "📢 *PANGGILAN ANTRIAN #{$queue->number}*\n\n"
                    . "Halo {$queue->patient->name},\n"
                    . "Nomor antrian Anda *#{$queue->number}* untuk *{$queue->doctor->name}* saat ini *SEDANG DIPANGGIL* (Panggilan ke-{$callCount}).\n\n"
                    . "Silakan segera menuju ke ruang periksa. Terima kasih!";

                $this->waManager->send($queue->clinic, $queue->patient->phone, $msg, $queue->patient);
            }

            // Pemicu otomatis pengingat giliran dekat bagi pasien 2 nomor setelahnya
            $this->reminderService->checkAndTriggerNearTurn($queue);

            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
