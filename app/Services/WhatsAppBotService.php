<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\ConversationState;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class WhatsAppBotService
{
    public function __construct(
        protected QueueService $queueService,
        protected WhatsAppManager $waManager
    ) {}

    /**
     * Process incoming message from WhatsApp
     */
    public function handleIncoming(Clinic $clinic, string $phone, string $incomingText): string
    {
        $text = trim($incomingText);
        $upper = strtoupper($text);

        // Fetch or create conversation state
        $state = ConversationState::where('phone', $phone)->first();
        if ($state && $state->isExpired()) {
            $state->delete();
            $state = null;
        }

        // Global commands
        if (in_array($upper, ['MENU', 'HELP', 'BANTUAN', 'HALO', 'HI', 'HAI'])) {
            if ($state) $state->delete();
            return $this->getMainMenu($clinic);
        }

        if ($upper === 'BATAL' || $upper === 'BATALKAN') {
            return $this->handleCancelFlow($clinic, $phone);
        }

        if ($upper === 'CEK' || $upper === 'STATUS') {
            return $this->handleCheckFlow($clinic, $phone);
        }

        if ($upper === 'DAFTAR' || $upper === 'ANTRI') {
            return $this->startRegistrationFlow($clinic, $phone);
        }

        // If there's an ongoing conversation state, continue it
        if ($state) {
            return $this->continueRegistrationFlow($clinic, $phone, $state, $text);
        }

        // Default fallback
        return $this->getMainMenu($clinic);
    }

    protected function getMainMenu(Clinic $clinic): string
    {
        return "🏥 *Selamat datang di {$clinic->name}*\n\n"
            . "Silakan balas pesan ini dengan pilihan berikut:\n"
            . "• *DAFTAR* : Pendaftaran antrian dokter\n"
            . "• *CEK* : Cek posisi & nomor antrian Anda\n"
            . "• *BATAL* : Batalkan antrian aktif\n"
            . "• *MENU* : Tampilkan menu utama ini\n\n"
            . "Layanan pendaftaran dibuka untuk hari ini hingga 3 hari ke depan.";
    }

    protected function startRegistrationFlow(Clinic $clinic, string $phone): string
    {
        $patient = Patient::where('phone', $phone)->first();

        if (!$patient) {
            ConversationState::updateOrCreate(
                ['phone' => $phone],
                [
                    'clinic_id' => $clinic->id,
                    'step' => 'ASK_NAME',
                    'context' => [],
                    'expires_at' => now()->addMinutes(15),
                ]
            );

            return "Halo! Nomor Anda belum terdaftar di sistem kami.\n\n"
                . "Silakan ketik *Nama Lengkap* Anda:";
        }

        return $this->showDoctorList($clinic, $phone, $patient);
    }

    protected function showDoctorList(Clinic $clinic, string $phone, Patient $patient): string
    {
        $doctors = $clinic->doctors()->where('clinic_doctors.is_active', true)->get();

        if ($doctors->isEmpty()) {
            return "Mohon maaf, saat ini belum ada dokter aktif yang tersedia di {$clinic->name}.";
        }

        $list = [];
        $doctorOptions = [];
        foreach ($doctors as $index => $doc) {
            $num = $index + 1;
            $doctorOptions[$num] = $doc->id;
            $list[] = "{$num}. *{$doc->name}* ({$doc->specialty})";
        }

        ConversationState::updateOrCreate(
            ['phone' => $phone],
            [
                'clinic_id' => $clinic->id,
                'step' => 'SELECT_DOCTOR',
                'context' => [
                    'patient_id' => $patient->id,
                    'doctor_options' => $doctorOptions,
                ],
                'expires_at' => now()->addMinutes(15),
            ]
        );

        return "Halo *{$patient->name}*!\nSilakan pilih dokter yang ingin Anda tuju:\n\n"
            . implode("\n", $list) . "\n\n"
            . "Balas dengan angka pilihan dokter (contoh: *1*).";
    }

    protected function continueRegistrationFlow(Clinic $clinic, string $phone, ConversationState $state, string $input): string
    {
        $step = $state->step;
        $context = $state->context ?? [];

        switch ($step) {
            case 'ASK_NAME':
                $name = trim($input);
                if (strlen($name) < 2) {
                    return "Nama terlalu pendek. Silakan masukkan nama lengkap Anda:";
                }

                $context['name'] = $name;
                $state->update([
                    'step' => 'ASK_NIK',
                    'context' => $context,
                    'expires_at' => now()->addMinutes(15),
                ]);

                return "Terima kasih, {$name}.\n\n"
                    . "Masukkan *16 digit NIK (KTP)* Anda, atau ketik *LEWATI* jika belum memiliki KTP:";

            case 'ASK_NIK':
                $nikInput = strtoupper(trim($input));
                $nik = null;
                if ($nikInput !== 'LEWATI') {
                    $cleaned = preg_replace('/[^0-9]/', '', $nikInput);
                    if (strlen($cleaned) !== 16) {
                        return "Format NIK tidak valid. Masukkan 16 digit angka NIK atau ketik *LEWATI*:";
                    }
                    $nik = $cleaned;
                }

                $context['nik'] = $nik;
                $state->update([
                    'step' => 'ASK_INSURANCE',
                    'context' => $context,
                    'expires_at' => now()->addMinutes(15),
                ]);

                return "Pilih jenis jaminan/asuransi pembayaran:\n"
                    . "1. *Umum / Pribadi*\n"
                    . "2. *BPJS Kesehatan*\n"
                    . "3. *Asuransi Lainnya*\n\n"
                    . "Balas dengan angka *1*, *2*, atau *3*:";

            case 'ASK_INSURANCE':
                $insChoice = trim($input);
                $insType = match ($insChoice) {
                    '2' => 'BPJS',
                    '3' => 'OTHER',
                    default => 'NONE',
                };

                // Create or update Patient
                $patient = Patient::create([
                    'name' => $context['name'],
                    'phone' => $phone,
                    'nik' => $context['nik'] ?? null,
                    'insurance_type' => $insType,
                    'consent_at' => now(),
                ]);

                // Link to clinic
                $clinic->patients()->syncWithoutDetaching([$patient->id]);

                return $this->showDoctorList($clinic, $phone, $patient);

            case 'SELECT_DOCTOR':
                $choice = trim($input);
                $doctorOptions = $context['doctor_options'] ?? [];

                if (!isset($doctorOptions[$choice])) {
                    return "Pilihan tidak valid. Silakan balas dengan angka nomor dokter yang tertera di atas.";
                }

                $doctorId = $doctorOptions[$choice];
                $doctor = Doctor::find($doctorId);

                // Show available booking dates (H-3 to H = 3 days)
                $bookingDates = $this->queueService->getAvailableBookingDates(3);
                $dateList = [];
                $dateOptions = [];

                foreach ($bookingDates as $idx => $d) {
                    $num = $idx + 1;
                    $dateOptions[$num] = $d['date'];

                    // Check schedule availability
                    $scheduleInfo = $this->queueService->resolveSchedule($clinic->id, $doctorId, Carbon::parse($d['date']));
                    if ($scheduleInfo) {
                        $statusStr = $scheduleInfo['is_full']
                            ? "❌ (Penuh)"
                            : "✅ Sisa kuota: {$scheduleInfo['remaining_quota']}";
                        $dateList[] = "{$num}. *{$d['formatted']}* [{$scheduleInfo['start_time']} - {$scheduleInfo['end_time']}] {$statusStr}";
                    } else {
                        $dateList[] = "{$num}. *{$d['formatted']}* (Libur / Tidak Praktik)";
                    }
                }

                $context['doctor_id'] = $doctorId;
                $context['date_options'] = $dateOptions;

                $state->update([
                    'step' => 'SELECT_DATE',
                    'context' => $context,
                    'expires_at' => now()->addMinutes(15),
                ]);

                return "Anda memilih: *{$doctor->name}*\n\n"
                    . "Pilih tanggal berobat:\n"
                    . implode("\n", $dateList) . "\n\n"
                    . "Balas dengan nomor pilihan tanggal (contoh: *1*):";

            case 'SELECT_DATE':
                $choice = trim($input);
                $dateOptions = $context['date_options'] ?? [];

                if (!isset($dateOptions[$choice])) {
                    return "Pilihan tanggal tidak valid. Silakan balas dengan nomor yang sesuai:";
                }

                $selectedDate = $dateOptions[$choice];
                $context['selected_date'] = $selectedDate;

                $state->update([
                    'step' => 'ASK_COMPLAINT',
                    'context' => $context,
                    'expires_at' => now()->addMinutes(15),
                ]);

                return "Tuliskan *Keluhan Utama / Alasan Kunjungan* Anda secara singkat (contoh: *Demam 2 hari, batuk pilek, periksa tensi*), atau ketik *LEWATI* jika tidak ingin mengisi:";

            case 'ASK_COMPLAINT':
                $complaintInput = trim($input);
                $complaint = (strtoupper($complaintInput) === 'LEWATI' || $complaintInput === '-') ? null : $complaintInput;

                $selectedDate = $context['selected_date'];
                $doctorId = $context['doctor_id'];
                $patientId = $context['patient_id'];

                try {
                    $queue = $this->queueService->registerQueue(
                        $clinic->id,
                        $doctorId,
                        $patientId,
                        $selectedDate,
                        Queue::SOURCE_WHATSAPP,
                        null,
                        $complaint
                    );

                    // Registration complete -> clear state
                    $state->delete();

                    $doctor = Doctor::find($doctorId);
                    $estTime = $this->queueService->calculateEstimatedTime($queue);
                    $formattedDate = Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y');

                    $keluhanText = $complaint ? "Keluhan: _{$complaint}_\n" : "";

                    return "🎉 *PENDAFTARAN ANTRIAN BERHASIL!*\n\n"
                        . "Klinik: *{$clinic->name}*\n"
                        . "Dokter: *{$doctor->name}*\n"
                        . "Tanggal: *{$formattedDate}*\n"
                        . $keluhanText
                        . "━━━━━━━━━━━━━━━━━━━━\n"
                        . "Nomor Antrian: *#{$queue->number}*\n"
                        . "Estimasi Dilayani: *{$estTime}*\n"
                        . "━━━━━━━━━━━━━━━━━━━━\n\n"
                        . "📌 *Petunjuk Pasien:*\n"
                        . "1. Harap hadir 15 menit sebelum estimasi waktu pemeriksaan.\n"
                        . "2. Anda akan menerima notifikasi pengingat H-1 dan saat giliran mendekat.\n"
                        . "3. Ketik *CEK* kapan saja untuk memantau status antrian.";

                } catch (Exception $e) {
                    return "⚠️ Pendaftaran belum berhasil: " . $e->getMessage() . "\n\nSilakan pilih tanggal lain atau ketik *MENU*.";
                }

            default:
                $state->delete();
                return $this->getMainMenu($clinic);
        }
    }

    protected function handleCheckFlow(Clinic $clinic, string $phone): string
    {
        $patient = Patient::where('phone', $phone)->first();
        if (!$patient) {
            return "Nomor Anda belum terdaftar. Silakan ketik *DAFTAR* untuk mengambil antrian.";
        }

        $today = Carbon::today()->toDateString();
        $activeQueues = Queue::with('doctor')
            ->where('clinic_id', $clinic->id)
            ->where('patient_id', $patient->id)
            ->where('queue_date', '>=', $today)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED, Queue::STATUS_IN_SERVICE])
            ->orderBy('queue_date')
            ->get();

        if ($activeQueues->isEmpty()) {
            return "Anda tidak memiliki antrian aktif saat ini di {$clinic->name}.\n\nKetik *DAFTAR* untuk mengambil antrian baru.";
        }

        $res = ["📋 *STATUS ANTRIAN AKTIF ANDA*"];

        foreach ($activeQueues as $q) {
            $formattedDate = $q->queue_date->locale('id')->translatedFormat('l, d M Y');
            $estTime = $this->queueService->calculateEstimatedTime($q);

            $patientsAhead = Queue::where('clinic_id', $clinic->id)
                ->where('doctor_id', $q->doctor_id)
                ->where('queue_date', $q->queue_date)
                ->where('number', '<', $q->number)
                ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED])
                ->count();

            $statusText = match ($q->status) {
                Queue::STATUS_CALLED => "🚨 *SEDANG DIPANGGIL* (Silakan menuju ruangan)",
                Queue::STATUS_IN_SERVICE => "🩺 *SEDANG DALAM PEMERIKSAAN*",
                default => "Menunggu ({$patientsAhead} pasien di depan Anda)",
            };

            $res[] = "Dokter: *{$q->doctor->name}*\n"
                . "Tanggal: {$formattedDate}\n"
                . "Nomor Antrian: *#{$q->number}*\n"
                . "Status: {$statusText}\n"
                . "Estimasi Jam: *{$estTime}*\n";
        }

        return implode("\n━━━━━━━━━━━━━━━━━━━━\n", $res);
    }

    protected function handleCancelFlow(Clinic $clinic, string $phone): string
    {
        $patient = Patient::where('phone', $phone)->first();
        if (!$patient) {
            return "Nomor Anda belum terdaftar.";
        }

        $today = Carbon::today()->toDateString();
        $queue = Queue::with('doctor')
            ->where('clinic_id', $clinic->id)
            ->where('patient_id', $patient->id)
            ->where('queue_date', '>=', $today)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_SKIPPED])
            ->latest('queue_date')
            ->first();

        if (!$queue) {
            return "Anda tidak memiliki antrian yang dapat dibatalkan saat ini.";
        }

        $queue->update(['status' => Queue::STATUS_CANCELLED]);

        return "✅ Antrian Anda untuk dokter *{$queue->doctor->name}* pada tanggal *{$queue->queue_date->format('d/m/Y')}* (Nomor #{$queue->number}) telah *DIBATALKAN*.\n\nKuota antrian telah dibebaskan. Terima kasih!";
    }
}

