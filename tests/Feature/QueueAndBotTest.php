<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\QueueService;
use App\Services\WhatsAppBotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueAndBotTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected Doctor $doctor;
    protected DoctorSchedule $schedule;
    protected QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $this->clinic = Clinic::create([
            'name' => 'Klinik Uji Sehat',
            'slug' => 'klinik-uji',
            'phone' => '+628123456789',
        ]);

        $this->doctor = Doctor::create([
            'name' => 'dr. Andika Pratama',
            'specialty' => 'Umum',
            'avg_minutes_per_patient' => 15,
        ]);

        $this->clinic->doctors()->attach($this->doctor->id, ['room_number' => 'R1', 'is_active' => true]);

        // Schedule for today
        $this->schedule = DoctorSchedule::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'weekday' => Carbon::today()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'quota' => 2, // low quota for testing limits
            'is_active' => true,
        ]);
    }

    public function test_queue_registration_within_3_days_window(): void
    {
        $patient = Patient::create([
            'name' => 'Budi Santoso',
            'phone' => '+628111999888',
            'nik' => '3201012304900001',
            'insurance_type' => 'BPJS',
        ]);

        $today = Carbon::today()->toDateString();
        $queue = $this->queueService->registerQueue(
            $this->clinic->id,
            $this->doctor->id,
            $patient->id,
            $today
        );

        $this->assertEquals(1, $queue->number);
        $this->assertEquals(Queue::STATUS_WAITING, $queue->status);

        // Next patient gets number 2
        $patient2 = Patient::create(['name' => 'Siti', 'phone' => '+628111999777']);
        $queue2 = $this->queueService->registerQueue(
            $this->clinic->id,
            $this->doctor->id,
            $patient2->id,
            $today
        );
        $this->assertEquals(2, $queue2->number);

        // Exceeding quota throws exception
        $patient3 = Patient::create(['name' => 'Rudi', 'phone' => '+628111999666']);
        $this->expectException(\Exception::class);
        $this->queueService->registerQueue(
            $this->clinic->id,
            $this->doctor->id,
            $patient3->id,
            $today
        );
    }

    public function test_calling_patient_three_times_marks_as_skipped(): void
    {
        $patient = Patient::create(['name' => 'Budi', 'phone' => '+628111999888']);
        $queue = $this->queueService->registerQueue(
            $this->clinic->id,
            $this->doctor->id,
            $patient->id,
            Carbon::today()->toDateString()
        );

        // 1st call
        $res1 = $this->queueService->callQueue($queue);
        $this->assertEquals('CALLED', $res1['action']);
        $this->assertEquals(1, $queue->fresh()->call_count);
        $this->assertEquals(Queue::STATUS_CALLED, $queue->fresh()->status);

        // 2nd call
        $res2 = $this->queueService->callQueue($queue->fresh());
        $this->assertEquals(2, $queue->fresh()->call_count);

        // 3rd call
        $res3 = $this->queueService->callQueue($queue->fresh());
        $this->assertEquals(3, $queue->fresh()->call_count);
        $this->assertEquals(Queue::STATUS_CALLED, $queue->fresh()->status);

        // 4th call exceeds limit -> marked SKIPPED
        $res4 = $this->queueService->callQueue($queue->fresh());
        $this->assertEquals('SKIPPED', $res4['action']);
        $this->assertEquals(Queue::STATUS_SKIPPED, $queue->fresh()->status);
    }

    public function test_display_data_api_endpoint(): void
    {
        $patient = Patient::create(['name' => 'Doni', 'phone' => '+628111999777']);
        $queue = $this->queueService->registerQueue(
            $this->clinic->id,
            $this->doctor->id,
            $patient->id,
            Carbon::today()->toDateString()
        );
        $this->queueService->callQueue($queue);

        $response = $this->getJson("/api/display/{$this->clinic->slug}/data");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'clinic_name',
                'date',
                'formatted_date',
                'current_call' => [
                    'number',
                    'doctor',
                    'room',
                    'status',
                ],
                'next_queues',
                'doctor_queues',
                'timestamp',
            ]);

        $this->assertEquals($queue->number, $response->json('current_call.number'));

        // When queue transitions to IN_SERVICE, display continues showing it
        $this->queueService->startService($queue);
        $resInService = $this->getJson("/api/display/{$this->clinic->slug}/data");
        $this->assertEquals('IN_SERVICE', $resInService->json('current_call.status'));
        $this->assertEquals('Sedang Dilayani', $resInService->json('current_call.status_label'));
    }

    public function test_whatsapp_bot_registration_flow(): void
    {
        $bot = app(WhatsAppBotService::class);
        $phone = '+628999888777';

        // 1. Initial greeting / DAFTAR asks for name
        $reply1 = $bot->handleIncoming($this->clinic, $phone, 'DAFTAR');
        $this->assertStringContainsString('Silakan ketik *Nama Lengkap*', $reply1);

        // 2. Submit name -> asks for NIK
        $reply2 = $bot->handleIncoming($this->clinic, $phone, 'Dian Permata');
        $this->assertStringContainsString('16 digit NIK', $reply2);

        // 3. Submit NIK -> asks for Insurance
        $reply3 = $bot->handleIncoming($this->clinic, $phone, '3171015504920005');
        $this->assertStringContainsString('Pilih jenis jaminan/asuransi', $reply3);

        // 4. Submit BPJS (2) -> shows doctor list
        $reply4 = $bot->handleIncoming($this->clinic, $phone, '2');
        $this->assertStringContainsString('dr. Andika Pratama', $reply4);

        // 5. Select doctor 1 -> shows booking dates (H-3 to H)
        $reply5 = $bot->handleIncoming($this->clinic, $phone, '1');
        $this->assertStringContainsString('Pilih tanggal berobat', $reply5);

        // 6. Select date 1 -> asks for complaint
        $reply6 = $bot->handleIncoming($this->clinic, $phone, '1');
        $this->assertStringContainsString('Keluhan Utama', $reply6);

        // 7. Submit complaint -> registers queue successfully
        $reply7 = $bot->handleIncoming($this->clinic, $phone, 'Demam tinggi dan pusing');
        $this->assertStringContainsString('PENDAFTARAN ANTRIAN BERHASIL', $reply7);
        $this->assertStringContainsString('#1', $reply7);
        $this->assertStringContainsString('Demam tinggi dan pusing', $reply7);

        // Verify patient saved with BPJS and NIK
        $savedPatient = Patient::where('phone', $phone)->first();
        $this->assertNotNull($savedPatient);
        $this->assertEquals('Dian Permata', $savedPatient->name);
        $this->assertEquals('3171015504920005', $savedPatient->nik);
        $this->assertEquals('BPJS', $savedPatient->insurance_type);
    }
}

