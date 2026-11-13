<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\QueueService;
use App\Services\ReminderService;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected WhatsAppManager $waManager,
        protected ReminderService $reminderService
    ) {}

    /**
     * Dashboard antrian resepsionis
     */
    public function index(Request $request): View
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        $date = $request->input('date', Carbon::today()->toDateString());
        $doctorId = $request->input('doctor_id');

        $doctors = $clinic->doctors()->where('clinic_doctors.is_active', true)->get();
        if (!$doctorId && $doctors->isNotEmpty()) {
            $doctorId = $doctors->first()->id;
        }

        $queuesQuery = Queue::with(['doctor', 'patient'])
            ->where('clinic_id', $clinic->id)
            ->where('queue_date', $date);

        if ($doctorId) {
            $queuesQuery->where('doctor_id', $doctorId);
        }

        $queues = $queuesQuery->orderBy('number')->get();

        $selectedDoctor = $doctors->firstWhere('id', $doctorId);
        $scheduleInfo = $selectedDoctor
            ? $this->queueService->resolveSchedule($clinic->id, $selectedDoctor->id, Carbon::parse($date))
            : null;

        return view('queue.index', compact(
            'clinic',
            'doctors',
            'selectedDoctor',
            'date',
            'queues',
            'scheduleInfo'
        ));
    }

    /**
     * Public Waiting Room Display
     */
    public function display(Request $request, string $clinicSlug): View
    {
        $clinic = Clinic::where('slug', $clinicSlug)->firstOrFail();
        $date = $request->input('date', Carbon::today()->toDateString());
        $doctorId = $request->input('doctor_id');
        $doctors = $clinic->doctors()->where('clinic_doctors.is_active', true)->get();

        return view('queue.display', compact('clinic', 'date', 'doctorId', 'doctors'));
    }

    /**
     * Resepsionis panggil nomor antrian (1x..3x)
     */
    public function call(Queue $queue): RedirectResponse
    {
        try {
            $result = $this->queueService->callQueue($queue);

            // Send WhatsApp notification if registered via WhatsApp
            if ($queue->patient && $queue->patient->phone) {
                $callCount = $result['call_count'] ?? 1;
                $msg = "📢 *PANGGILAN ANTRIAN #{$queue->number}*\n\n"
                    . "Halo {$queue->patient->name},\n"
                    . "Nomor antrian Anda *#{$queue->number}* untuk *{$queue->doctor->name}* saat ini *SEDANG DIPANGGIL* (Panggilan ke-{$callCount}).\n\n"
                    . "Silakan segera menuju ke ruang periksa. Terima kasih!";

                $this->waManager->send($queue->clinic, $queue->patient->phone, $msg, $queue->patient);
            }

            // Otomasi Pengingat: Pemicu Near-Turn Reminder untuk pasien di sisa 2 antrian berikutnya
            $this->reminderService->checkAndTriggerNearTurn($queue);

            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mulai pelayanan dokter
     */
    public function start(Queue $queue): RedirectResponse
    {
        $this->queueService->startService($queue);
        return back()->with('success', "Pemeriksaan pasien nomor #{$queue->number} telah dimulai.");
    }

    /**
     * Selesai pelayanan dokter
     */
    public function finish(Request $request, Queue $queue): RedirectResponse
    {
        $notes = $request->input('doctor_notes');
        $this->queueService->finishService($queue, $notes);

        // Otomasi Pengingat: Cek dan ingatkan pasien berikutnya
        $this->reminderService->checkAndTriggerNearTurn($queue);

        return back()->with('success', "Pelayanan pasien nomor #{$queue->number} telah selesai.");
    }

    /**
     * Pemicu manual kirim pengingat jadwal besok (H-1)
     */
    public function triggerH1Reminders(Request $request): RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic;

        $res = $this->reminderService->sendH1Reminders($clinic?->id);

        return back()->with('success', "Pengingat H-1 selesai diproses: {$res['sent']} pesan WhatsApp terkirim (Total antrian besok: {$res['total_eligible']}).");
    }

    /**
     * Lewati antrian (jika tidak hadir)
     */
    public function skip(Queue $queue): RedirectResponse
    {
        $queue->update(['status' => Queue::STATUS_SKIPPED]);
        return back()->with('warning', "Nomor #{$queue->number} ditandai dilewati (SKIPPED).");
    }

    /**
     * Batalkan antrian
     */
    public function cancel(Queue $queue): RedirectResponse
    {
        $queue->update(['status' => Queue::STATUS_CANCELLED]);
        return back()->with('info', "Nomor #{$queue->number} berhasil dibatalkan.");
    }

    /**
     * Tambah antrian manual langsung dari klinik (Walk-in)
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_id' => 'required|uuid',
            'doctor_id' => 'required|uuid',
            'queue_date' => 'required|date',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'nik' => 'nullable|string|size:16',
            'insurance_type' => 'required|in:NONE,BPJS,OTHER',
            'insurance_number' => 'nullable|string|max:50',
        ]);

        try {
            // Find or create patient
            $patient = Patient::firstOrCreate(
                ['phone' => $validated['phone']],
                [
                    'name' => $validated['name'],
                    'nik' => $validated['nik'],
                    'insurance_type' => $validated['insurance_type'],
                    'insurance_number' => $validated['insurance_number'],
                    'consent_at' => now(),
                ]
            );

            // Register queue
            $queue = $this->queueService->registerQueue(
                $validated['clinic_id'],
                $validated['doctor_id'],
                $patient->id,
                $validated['queue_date'],
                Queue::SOURCE_MANUAL
            );

            return back()->with('success', "Pasien manual berhasil didaftarkan dengan nomor antrian #{$queue->number}.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

