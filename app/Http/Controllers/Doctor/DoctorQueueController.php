<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\ClinicalProcedure;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\Icd10Code;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
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
     * Tampilan Antrian Ruang Praktik Dokter & Rekam Medis Elektronik (RME)
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

        // Ambil semua antrian hari ini untuk dokter ini lengkap dengan encounter & rekam medis
        $allQueues = Queue::with(['patient', 'schedule', 'encounter.medicalRecord.primaryIcd10', 'encounter.prescriptions.items'])
            ->where('clinic_id', $clinic->id)
            ->where('doctor_id', $selectedDoctor->id)
            ->whereDate('queue_date', $date)
            ->orderBy('number')
            ->get();

        // 1. Pasien Aktif (Sedang diperiksa atau baru saja dipanggil)
        $activeQueue = $allQueues->firstWhere('status', Queue::STATUS_IN_SERVICE)
            ?? $allQueues->firstWhere('status', Queue::STATUS_CALLED);

        // Ambil data encounter aktif, rekam medis aktif, dan riwayat rekam medis pasien sebelumnya
        $activeEncounter = null;
        $patientHistory = collect();
        if ($activeQueue) {
            $activeEncounter = Encounter::with(['medicalRecord.primaryIcd10', 'prescriptions.items', 'clinicalProcedures'])
                ->firstWhere('queue_id', $activeQueue->id);

            $patientHistory = MedicalRecord::with(['encounter', 'primaryIcd10', 'doctor'])
                ->where('patient_id', $activeQueue->patient_id)
                ->when($activeEncounter?->medicalRecord?->id, function ($q, $id) {
                    $q->where('id', '!=', $id);
                })
                ->latest()
                ->take(5)
                ->get();
        }

        // Top 20 Common ICD-10 untuk saran cepat dokter
        $commonIcd10 = Icd10Code::where('is_common', true)->orderBy('code')->take(20)->get();

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
            'activeEncounter',
            'patientHistory',
            'commonIcd10',
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
     * Mulai pelayanan / pemeriksaan pasien & inisialisasi Encounter
     */
    public function start(Queue $queue): RedirectResponse
    {
        $this->queueService->startService($queue);

        // Inisialisasi Encounter Kunjungan Pasien jika belum ada
        Encounter::firstOrCreate(
            ['queue_id' => $queue->id],
            [
                'clinic_id' => $queue->clinic_id,
                'patient_id' => $queue->patient_id,
                'doctor_id' => $queue->doctor_id,
                'encounter_date' => $queue->queue_date,
                'start_time' => now(),
                'encounter_type' => Encounter::TYPE_OUTPATIENT,
                'status' => Encounter::STATUS_IN_PROGRESS,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', "Pemeriksaan pasien #{$queue->number} ({$queue->patient->name}) telah dimulai.");
    }

    /**
     * Selesai pemeriksaan dengan penyimpanan Rekam Medis SOAP, ICD-10 & Resep Obat
     */
    public function finish(Request $request, Queue $queue): RedirectResponse
    {
        // 1. Validasi input formulir Rekam Medis
        $validated = $request->validate([
            'complaint' => 'nullable|string|max:1000',
            'history_of_present_illness' => 'nullable|string|max:2000',
            'allergy_history' => 'nullable|string|max:500',
            'systolic' => 'nullable|integer|min:40|max:300',
            'diastolic' => 'nullable|integer|min:20|max:200',
            'heart_rate' => 'nullable|integer|min:30|max:250',
            'respiratory_rate' => 'nullable|integer|min:5|max:80',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'weight' => 'nullable|numeric|min:1|max:300',
            'height' => 'nullable|numeric|min:20|max:250',
            'physical_examination' => 'nullable|string|max:2000',
            'primary_icd10_code' => 'nullable|string|exists:icd10_codes,code',
            'diagnosis_notes' => 'nullable|string|max:2000',
            'doctor_notes' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:2000',
            'medicines' => 'nullable|array',
            'medicines.*.name' => 'nullable|string|max:255',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.form' => 'nullable|string|max:50',
            'medicines.*.frequency' => 'nullable|string|max:100',
            'medicines.*.quantity' => 'nullable|integer|min:1|max:500',
            'medicines.*.instructions' => 'nullable|string|max:255',
            'procedure_name' => 'nullable|string|max:255',
            'procedure_cost' => 'nullable|numeric|min:0',
            'procedure_notes' => 'nullable|string|max:1000',
        ]);

        // 2. Pastikan Encounter Kunjungan Pasien Ada
        $encounter = Encounter::firstOrCreate(
            ['queue_id' => $queue->id],
            [
                'clinic_id' => $queue->clinic_id,
                'patient_id' => $queue->patient_id,
                'doctor_id' => $queue->doctor_id,
                'encounter_date' => $queue->queue_date,
                'start_time' => $queue->started_at ?? now(),
                'encounter_type' => Encounter::TYPE_OUTPATIENT,
                'status' => Encounter::STATUS_IN_PROGRESS,
                'created_by' => auth()->id(),
            ]
        );

        // 3. Simpan / Perbarui Catatan Medis SOAP & Tanda Vital
        $medicalRecord = MedicalRecord::updateOrCreate(
            ['encounter_id' => $encounter->id],
            [
                'clinic_id' => $queue->clinic_id,
                'patient_id' => $queue->patient_id,
                'doctor_id' => $queue->doctor_id,
                'complaint' => $validated['complaint'] ?? $queue->complaint,
                'history_of_present_illness' => $validated['history_of_present_illness'] ?? null,
                'allergy_history' => $validated['allergy_history'] ?? null,
                'systolic' => $validated['systolic'] ?? null,
                'diastolic' => $validated['diastolic'] ?? null,
                'heart_rate' => $validated['heart_rate'] ?? null,
                'respiratory_rate' => $validated['respiratory_rate'] ?? null,
                'temperature' => $validated['temperature'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'height' => $validated['height'] ?? null,
                'physical_examination' => $validated['physical_examination'] ?? null,
                'primary_icd10_code' => $validated['primary_icd10_code'] ?? null,
                'diagnosis_notes' => $validated['diagnosis_notes'] ?? $validated['doctor_notes'] ?? null,
                'treatment_plan' => $validated['treatment_plan'] ?? null,
            ]
        );

        $medicalRecord->bmi = $medicalRecord->calculateBmi();
        $medicalRecord->save();

        // 4. Simpan Resep Obat jika ada obat yang diresepkan
        $medicines = $validated['medicines'] ?? [];
        $validMedicines = array_filter($medicines, fn($m) => !empty($m['name']));
        if (!empty($validMedicines)) {
            $prescription = Prescription::firstOrCreate(
                ['encounter_id' => $encounter->id],
                [
                    'clinic_id' => $queue->clinic_id,
                    'patient_id' => $queue->patient_id,
                    'doctor_id' => $queue->doctor_id,
                    'status' => Prescription::STATUS_PRESCRIBED,
                ]
            );

            $prescription->items()->delete();
            foreach ($validMedicines as $med) {
                $prescription->items()->create([
                    'medicine_name' => $med['name'],
                    'dosage' => $med['dosage'] ?? null,
                    'form' => $med['form'] ?? 'Tablet',
                    'frequency' => $med['frequency'] ?? '3x1 sehari sesudah makan',
                    'quantity' => (int) ($med['quantity'] ?? 1),
                    'instructions' => $med['instructions'] ?? null,
                ]);
            }
        }

        // 5. Simpan Tindakan Medis jika ada
        if (!empty($validated['procedure_name'])) {
            ClinicalProcedure::updateOrCreate(
                ['encounter_id' => $encounter->id, 'procedure_name' => $validated['procedure_name']],
                [
                    'clinic_id' => $queue->clinic_id,
                    'patient_id' => $queue->patient_id,
                    'doctor_id' => $queue->doctor_id,
                    'cost' => $validated['procedure_cost'] ?? 0,
                    'notes' => $validated['procedure_notes'] ?? null,
                ]
            );
        }

        // 6. Tandai Encounter selesai
        $encounter->update([
            'status' => Encounter::STATUS_COMPLETED,
            'end_time' => now(),
        ]);

        // 7. Ringkasan Diagnosa untuk Kolom Doctor Notes di tabel antrian
        $icdCode = $medicalRecord->primaryIcd10;
        $summaryDiagnosis = $icdCode
            ? "[{$icdCode->code}] {$icdCode->name_id}"
            : ($validated['diagnosis_notes'] ?? $validated['doctor_notes'] ?? 'Konsultasi selesai');

        $this->queueService->finishService($queue, $summaryDiagnosis);

        // 8. Otomasi Pengingat: Pemicu pengingat giliran dekat bagi pasien berikutnya
        $this->reminderService->checkAndTriggerNearTurn($queue);

        return back()->with('success', "Pemeriksaan pasien #{$queue->number} ({$queue->patient->name}) selesai. Rekam medis SOAP dan resep obat berhasil disimpan.");
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
