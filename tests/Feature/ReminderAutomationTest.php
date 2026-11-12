<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use App\Services\QueueService;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReminderAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected Doctor $doctor;
    protected DoctorSchedule $schedule;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.fonnte.com/*' => Http::response(['status' => true, 'id' => 'msg_test_123'], 200),
        ]);

        $this->clinic = Clinic::create([
            'name' => 'Klinik Pengingat Uji',
            'slug' => 'klinik-pengingat',
            'phone' => '+628123456789',
            'whatsapp_provider' => 'FONNTE',
            'whatsapp_config' => ['token' => 'dummy_test_token'],
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Staf Resepsionis',
            'email' => 'resepsionis@pengingat.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'dr. Santoso Sp.PD',
            'specialty' => 'Penyakit Dalam',
            'avg_minutes_per_patient' => 15,
        ]);

        $this->clinic->doctors()->attach($this->doctor->id, ['room_number' => 'R1', 'is_active' => true]);

        // Schedules for today and tomorrow
        $todayWeekday = Carbon::today()->dayOfWeek;
        $tomorrowWeekday = Carbon::tomorrow()->dayOfWeek;

        DoctorSchedule::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'weekday' => $todayWeekday,
            'start_time' => '08:00:00',
            'end_time' => '13:00:00',
            'quota' => 30,
            'is_active' => true,
        ]);

        if ($todayWeekday !== $tomorrowWeekday) {
            DoctorSchedule::create([
                'clinic_id' => $this->clinic->id,
                'doctor_id' => $this->doctor->id,
                'weekday' => $tomorrowWeekday,
                'start_time' => '08:00:00',
                'end_time' => '13:00:00',
                'quota' => 30,
                'is_active' => true,
            ]);
        }
    }

    public function test_h1_reminders_command_sends_to_tomorrow_patients(): void
    {
        $patient = Patient::create([
            'name' => 'Pasien Besok',
            'phone' => '+628123456701',
        ]);

        $tomorrow = Carbon::tomorrow()->toDateString();
        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'queue_date' => $tomorrow,
            'number' => 1,
            'status' => Queue::STATUS_WAITING,
            'source' => Queue::SOURCE_WHATSAPP,
        ]);

        $this->assertNull($queue->reminder_h1_sent_at);

        // Run artisan command
        $this->artisan('clinic:send-h1-reminders')
            ->assertExitCode(0);

        // Assert timestamp updated
        $this->assertNotNull($queue->fresh()->reminder_h1_sent_at);

        // Assert message log created
        $this->assertDatabaseHas('message_logs', [
            'clinic_id' => $this->clinic->id,
            'phone' => '+628123456701',
            'direction' => 'OUT',
        ]);

        // Running second time should not duplicate (already marked)
        $reminderService = app(ReminderService::class);
        $res = $reminderService->sendH1Reminders();
        $this->assertEquals(0, $res['total_eligible']);
    }

    public function test_near_turn_notification_triggers_when_calling_queue(): void
    {
        $today = Carbon::today()->toDateString();

        // Create 3 patients and queues for today: #1, #2, #3
        $p1 = Patient::create(['name' => 'Pasien Satu', 'phone' => '+628123456711']);
        $p2 = Patient::create(['name' => 'Pasien Dua', 'phone' => '+628123456722']);
        $p3 = Patient::create(['name' => 'Pasien Tiga', 'phone' => '+628123456733']);

        $q1 = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $p1->id,
            'queue_date' => $today,
            'number' => 1,
            'status' => Queue::STATUS_WAITING,
        ]);

        $q2 = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $p2->id,
            'queue_date' => $today,
            'number' => 2,
            'status' => Queue::STATUS_WAITING,
        ]);

        $q3 = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $p3->id,
            'queue_date' => $today,
            'number' => 3,
            'status' => Queue::STATUS_WAITING,
        ]);

        // When receptionist calls queue #1
        $this->actingAs($this->staff)->post("/antrian/{$q1->id}/call");

        // Queue #1 is CALLED
        $this->assertEquals(Queue::STATUS_CALLED, $q1->fresh()->status);

        // Near-turn notification triggers for queue in line
        $this->assertTrue(
            $q2->fresh()->near_turn_sent_at !== null || $q3->fresh()->near_turn_sent_at !== null
        );

        $this->assertDatabaseHas('message_logs', [
            'direction' => 'OUT',
        ]);
    }

    public function test_manual_h1_reminder_trigger_from_dashboard(): void
    {
        $response = $this->actingAs($this->staff)->post('/antrian/reminders/h1');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
