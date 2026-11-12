<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DoctorPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected Doctor $doctor;
    protected User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success'], 200),
        ]);

        $this->clinic = Clinic::create([
            'slug' => 'klinik-sehat',
            'name' => 'Klinik Pratama Sehat',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '+6281234567890',
            'timezone' => 'Asia/Jakarta',
            'whatsapp_provider' => 'FONNTE',
            'whatsapp_config' => ['token' => 'sample_token_fonnte'],
            'settings' => ['max_booking_days' => 2, 'call_retry_limit' => 3],
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'dr. Budi Santoso, Sp.PD',
            'specialty' => 'Penyakit Dalam',
            'sip_number' => 'SIP-12345',
            'avg_minutes_per_patient' => 15,
            'is_active' => true,
        ]);

        $this->clinic->doctors()->attach($this->doctor->id, [
            'room_number' => 'Ruang 101',
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'name' => 'dr. Budi',
            'email' => 'dokter@klinik.id',
            'password' => bcrypt('password'),
            'role' => User::ROLE_DOCTOR,
            'is_active' => true,
        ]);
    }

    public function test_doctor_portal_requires_login(): void
    {
        $response = $this->get('/dokter/antrian');
        $response->assertRedirect('/login');
    }

    public function test_doctor_can_view_their_queue_dashboard(): void
    {
        $patient = Patient::create([
            'name' => 'Ahmad Faiz',
            'phone' => '+6281299990001',
            'insurance_type' => 'BPJS',
        ]);

        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 1,
            'status' => Queue::STATUS_WAITING,
            'source' => Queue::SOURCE_MANUAL,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->get('/dokter/antrian');

        $response->assertStatus(200);
        $response->assertSee('Ruang Dokter');
        $response->assertSee('dr. Budi Santoso, Sp.PD');
        $response->assertSee('Ahmad Faiz');
    }

    public function test_doctor_can_call_next_waiting_queue(): void
    {
        $patient = Patient::create([
            'name' => 'Siti Aminah',
            'phone' => '+6281299990002',
            'insurance_type' => 'NONE',
        ]);

        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 1,
            'status' => Queue::STATUS_WAITING,
            'source' => Queue::SOURCE_MANUAL,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post('/dokter/antrian/panggil-berikutnya', [
                'doctor_id' => $this->doctor->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals(Queue::STATUS_CALLED, $queue->fresh()->status);
        $this->assertEquals(1, $queue->fresh()->call_count);
    }

    public function test_doctor_can_start_and_finish_consultation_with_notes(): void
    {
        $patient = Patient::create([
            'name' => 'Rudi Hartono',
            'phone' => '+6281299990003',
            'insurance_type' => 'BPJS',
        ]);

        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 1,
            'status' => Queue::STATUS_CALLED,
            'source' => Queue::SOURCE_MANUAL,
            'call_count' => 1,
        ]);

        // Start consultation
        $startRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/start");

        $startRes->assertRedirect();
        $this->assertEquals(Queue::STATUS_IN_SERVICE, $queue->fresh()->status);
        $this->assertNotNull($queue->fresh()->started_at);

        // Finish consultation with notes
        $finishRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/finish", [
                'doctor_notes' => 'Diagnosa: Dispepsia. Terapi: Antasida 3x1 ac, Paracetamol bila demam.',
            ]);

        $finishRes->assertRedirect();
        $freshQueue = $queue->fresh();
        $this->assertEquals(Queue::STATUS_DONE, $freshQueue->status);
        $this->assertNotNull($freshQueue->finished_at);
        $this->assertStringContainsString('Dispepsia', $freshQueue->doctor_notes);
    }

    public function test_doctor_can_skip_and_recall_queue(): void
    {
        $patient = Patient::create([
            'name' => 'Bambang Sudirman',
            'phone' => '+6281299990004',
            'insurance_type' => 'NONE',
        ]);

        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 2,
            'status' => Queue::STATUS_CALLED,
            'source' => Queue::SOURCE_MANUAL,
            'call_count' => 3,
        ]);

        // Skip patient
        $skipRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/skip");

        $skipRes->assertRedirect();
        $this->assertEquals(Queue::STATUS_SKIPPED, $queue->fresh()->status);

        // Recall patient
        $recallRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/recall");

        $recallRes->assertRedirect();
        $this->assertEquals(Queue::STATUS_CALLED, $queue->fresh()->status);
    }

    public function test_login_as_doctor_redirects_to_doctor_portal(): void
    {
        $response = $this->post('/login', [
            'email' => 'dokter@klinik.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dokter/antrian');
    }
}
