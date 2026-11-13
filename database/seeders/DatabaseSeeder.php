<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Master Data ICD-10
        $this->call(Icd10Seeder::class);

        // 1. Create Default Clinic
        $clinic = Clinic::firstOrCreate(
            ['slug' => 'klinik-sehat'],
            [
                'name' => 'Klinik Pratama Sehat Sejahtera',
                'address' => 'Jl. Kesehatan No. 12, Kebayoran Baru, Jakarta Selatan',
                'phone' => '+6281234567890',
                'timezone' => 'Asia/Jakarta',
                'whatsapp_provider' => 'FONNTE',
                'whatsapp_config' => ['token' => 'sample_token_fonnte'],
                'settings' => [
                    'max_booking_days' => 2, // H+2 = 3 hari total
                    'call_retry_limit' => 3,
                ],
                'is_active' => true,
            ]
        );

        // 1.5 Create Global SuperAdmin User (clinic_id is null)
        User::firstOrCreate(
            ['email' => 'superadmin@kedokter.id'],
            [
                'clinic_id' => null,
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPERADMIN,
                'is_active' => true,
            ]
        );

        // 2. Create Admin & Staff Users
        User::firstOrCreate(
            ['email' => 'admin@klinik.id'],
            [
                'clinic_id' => $clinic->id,
                'name' => 'Admin Klinik',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'resepsionis@klinik.id'],
            [
                'clinic_id' => $clinic->id,
                'name' => 'Siti Resepsionis',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'is_active' => true,
            ]
        );

        // 3. Create Sample Doctors
        $doc1 = Doctor::firstOrCreate(
            ['name' => 'dr. Budi Santoso, Sp.PD'],
            [
                'sip_number' => 'SIP-501/DKK/2024',
                'specialty' => 'Poli Penyakit Dalam',
                'avg_minutes_per_patient' => 15,
                'is_active' => true,
            ]
        );

        // 3.1 Create Doctor User Account
        User::firstOrCreate(
            ['email' => 'dokter@klinik.id'],
            [
                'clinic_id' => $clinic->id,
                'doctor_id' => $doc1->id,
                'name' => 'dr. Budi Santoso, Sp.PD',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DOCTOR,
                'is_active' => true,
            ]
        );

        $doc2 = Doctor::firstOrCreate(
            ['name' => 'dr. Siti Rahma, Sp.A'],
            [
                'sip_number' => 'SIP-502/DKK/2024',
                'specialty' => 'Poli Spesialis Anak',
                'avg_minutes_per_patient' => 12,
                'is_active' => true,
            ]
        );

        $doc3 = Doctor::firstOrCreate(
            ['name' => 'dr. Hendra Wijaya, Sp.KG'],
            [
                'sip_number' => 'SIP-503/DKK/2024',
                'specialty' => 'Poli Gigi & Mulut',
                'avg_minutes_per_patient' => 20,
                'is_active' => true,
            ]
        );

        // Link Doctors to Clinic
        $clinic->doctors()->syncWithoutDetaching([
            $doc1->id => ['room_number' => 'Ruang 101', 'is_active' => true],
            $doc2->id => ['room_number' => 'Ruang 102', 'is_active' => true],
            $doc3->id => ['room_number' => 'Ruang 103', 'is_active' => true],
        ]);

        // 4. Create Weekly Schedules (Monday to Saturday)
        foreach ([$doc1, $doc2, $doc3] as $doctor) {
            for ($weekday = 0; $weekday <= 6; $weekday++) {
                DoctorSchedule::firstOrCreate(
                    [
                        'clinic_id' => $clinic->id,
                        'doctor_id' => $doctor->id,
                        'weekday' => $weekday,
                    ],
                    [
                        'start_time' => '08:00:00',
                        'end_time' => '14:00:00',
                        'quota' => 25,
                        'is_active' => true,
                    ]
                );
            }
        }

        // 5. Create Sample Patients (with NIK & BPJS)
        $patientsData = [
            [
                'name' => 'Ahmad Faisal',
                'phone' => '+628111222333',
                'nik' => '3171012304850001',
                'insurance_type' => 'BPJS',
                'insurance_number' => '0001234567891',
            ],
            [
                'name' => 'Dewi Lestari',
                'phone' => '+628222333444',
                'nik' => '3171015607900002',
                'insurance_type' => 'NONE',
                'insurance_number' => null,
            ],
            [
                'name' => 'Rian Hidayat',
                'phone' => '+628333444555',
                'nik' => '3171011502930003',
                'insurance_type' => 'BPJS',
                'insurance_number' => '0001234567893',
            ],
            [
                'name' => 'Nurul Aini',
                'phone' => '+628444555666',
                'nik' => '3171014811950004',
                'insurance_type' => 'OTHER',
                'insurance_number' => 'PRU-99887711',
            ],
            [
                'name' => 'Bambang Sugiono',
                'phone' => '+628555666777',
                'nik' => '3171010101800005',
                'insurance_type' => 'BPJS',
                'insurance_number' => '0001234567895',
            ],
        ];

        $today = Carbon::today()->toDateString();
        $scheduleDoc1 = DoctorSchedule::where('clinic_id', $clinic->id)
            ->where('doctor_id', $doc1->id)
            ->where('weekday', Carbon::today()->dayOfWeek)
            ->first();

        $complaints = [
            'Nyeri ulu hati & mual muntah sejak 2 hari yang lalu',
            'Demam tinggi 38.5°C, batuk kering dan sakit tenggorokan',
            'Kontrol rutin tekanan darah tinggi & evaluasi obat hipertensi',
            'Pusing berputar (vertigo) dan lemas',
            'Sesak napas ringan dan batuk berdahak',
        ];

        foreach ($patientsData as $index => $data) {
            $patient = Patient::firstOrCreate(
                ['phone' => $data['phone']],
                array_merge($data, ['consent_at' => now()])
            );

            $clinic->patients()->syncWithoutDetaching([$patient->id]);

            // Seed sample queues for today
            $queueNumber = $index + 1;
            $status = match ($queueNumber) {
                1 => Queue::STATUS_DONE,
                2 => Queue::STATUS_CALLED,
                default => Queue::STATUS_WAITING,
            };

            Queue::updateOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'doctor_id' => $doc1->id,
                    'queue_date' => $today,
                    'number' => $queueNumber,
                ],
                [
                    'doctor_schedule_id' => $scheduleDoc1?->id,
                    'patient_id' => $patient->id,
                    'status' => $status,
                    'complaint' => $complaints[$index] ?? 'Pemeriksaan kesehatan rutin',
                    'doctor_notes' => $status === Queue::STATUS_DONE ? 'Diagnosa: Dispepsia fungsional. Terapi: Antasida 3x1 ac, Ranitidine 150mg 2x1.' : null,
                    'source' => $index % 2 === 0 ? Queue::SOURCE_WHATSAPP : Queue::SOURCE_MANUAL,
                    'call_count' => $status === Queue::STATUS_CALLED ? 1 : ($status === Queue::STATUS_DONE ? 1 : 0),
                    'last_called_at' => $status === Queue::STATUS_CALLED ? now() : null,
                    'started_at' => $status === Queue::STATUS_DONE ? now()->subMinutes(25) : null,
                    'finished_at' => $status === Queue::STATUS_DONE ? now()->subMinutes(10) : null,
                    'registered_at' => now()->subHours(4 - $index),
                ]
            );
        }

        // 6. Message Templates
        $templates = [
            'REGISTER_SUCCESS' => "🎉 *PENDAFTARAN ANTRIAN BERHASIL*\nKlinik: {klinik}\nDokter: {dokter}\nTanggal: {tanggal}\nNomor Antrian: *#{nomor}*\nEstimasi Jam: {jam_estimasi}",
            'REMINDER_H1' => "🔔 *PENGINGAT JADWAL DOKTER BESOK*\nHalo {nama}, jadwal Anda untuk {dokter} besok ({tanggal}) dengan nomor antrian *#{nomor}*. Estimasi dilayani: {jam_estimasi}.",
            'NEAR_TURN' => "⏳ *GIRILAN ANDA SEMAKIN DEKAT*\nHalo {nama}, tersisa 2 pasien di depan nomor antrian Anda (*#{nomor}*). Silakan bersiap di ruang tunggu klinik.",
            'CALLED_1' => "🚨 *NOMOR ANTRIAN ANDA DIPANGGIL*\nNomor *#{nomor}* ({nama}), silakan segera masuk ke ruang periksa {dokter}.",
        ];

        foreach ($templates as $key => $body) {
            MessageTemplate::firstOrCreate(
                ['clinic_id' => $clinic->id, 'key' => $key],
                ['body' => $body]
            );
        }
    }
}
