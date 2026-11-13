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

    public function test_doctor_can_complete_consultation_with_full_emr_and_prescription(): void
    {
        $this->seed(\Database\Seeders\Icd10Seeder::class);

        $patient = Patient::create([
            'name' => 'Siti Rahmawati',
            'nik' => '3201015501900001',
            'phone' => '+6281299990005',
            'gender' => 'FEMALE',
            'insurance_type' => 'BPJS',
            'insurance_number' => '0001234567890',
        ]);

        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 2,
            'status' => Queue::STATUS_CALLED,
            'source' => Queue::SOURCE_MANUAL,
            'complaint' => 'Nyeri ulu hati dan kembung',
            'call_count' => 1,
        ]);

        // 1. Doctor starts examination
        $startRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/start");

        $startRes->assertRedirect();
        $this->assertEquals(Queue::STATUS_IN_SERVICE, $queue->fresh()->status);

        // 2. Doctor finishes with full EMR, ICD-10, and Prescription
        $finishRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/finish", [
                'complaint' => 'Nyeri ulu hati memberat 2 hari terakhir',
                'history_of_present_illness' => 'Nyeri dirasakan perih setelah makan pedas, disertai mual tanpa muntah.',
                'allergy_history' => 'Tidak ada alergi obat',
                'systolic' => 120,
                'diastolic' => 80,
                'heart_rate' => 76,
                'respiratory_rate' => 18,
                'temperature' => 36.5,
                'weight' => 60,
                'height' => 165,
                'physical_examination' => 'Abdomen: Nyeri tekan epigastrium (+), bising usus normal.',
                'primary_icd10_code' => 'K30',
                'diagnosis_notes' => 'Dispepsia fungsional eksaserbasi akut',
                'treatment_plan' => 'Makan teratur, hindari asam dan pedas, banyak istirahat.',
                'medicines' => [
                    [
                        'name' => 'Antasida Doen',
                        'dosage' => '500 mg',
                        'form' => 'Tablet',
                        'frequency' => '3x1 kunyah sebelum makan',
                        'quantity' => 15,
                        'instructions' => 'Dikunyah sebelum makan',
                    ],
                    [
                        'name' => 'Omeprazole',
                        'dosage' => '20 mg',
                        'form' => 'Kapsul',
                        'frequency' => '1x1 pagi',
                        'quantity' => 7,
                        'instructions' => 'Sebelum sarapan',
                    ],
                ],
                'procedure_name' => 'Pemeriksaan EKG Sederhana',
                'procedure_cost' => 50000,
                'procedure_notes' => 'Irama sinus normal',
            ]);

        $finishRes->assertRedirect();
        $freshQueue = $queue->fresh();
        $this->assertEquals(Queue::STATUS_DONE, $freshQueue->status);
        $this->assertNotNull($freshQueue->finished_at);
        $this->assertStringContainsString('K30', $freshQueue->doctor_notes);

        // Validate Encounter
        $encounter = \App\Models\Encounter::where('queue_id', $queue->id)->first();
        $this->assertNotNull($encounter);
        $this->assertEquals(\App\Models\Encounter::STATUS_COMPLETED, $encounter->status);

        // Validate MedicalRecord & Auto BMI (60 / 1.65^2 = ~22.0)
        $medicalRecord = $encounter->medicalRecord;
        $this->assertNotNull($medicalRecord);
        $this->assertEquals('K30', $medicalRecord->primary_icd10_code);
        $this->assertEquals(22.0, $medicalRecord->bmi);
        $this->assertEquals(120, $medicalRecord->systolic);
        $this->assertEquals(80, $medicalRecord->diastolic);
        $this->assertEquals('120/80', $medicalRecord->blood_pressure);
        $this->assertStringContainsString('epigastrium', $medicalRecord->physical_examination);

        // Validate Prescription & Items
        $prescription = $encounter->prescriptions->first();
        $this->assertNotNull($prescription);
        $this->assertCount(2, $prescription->items);
        $this->assertEquals('Antasida Doen', $prescription->items[0]->medicine_name);
        $this->assertEquals(15, $prescription->items[0]->quantity);

        // Validate Clinical Procedure
        $procedure = $encounter->clinicalProcedures->first();
        $this->assertNotNull($procedure);
        $this->assertEquals('Pemeriksaan EKG Sederhana', $procedure->procedure_name);
        $this->assertEquals(50000, $procedure->cost);
    }

    public function test_icd10_autocomplete_api_endpoint(): void
    {
        $this->seed(\Database\Seeders\Icd10Seeder::class);

        // Search by query "hiper"
        $response = $this->getJson('/api/icd10/search?q=hiper');
        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertTrue(collect($data)->contains(fn($item) => $item['code'] === 'I10'));

        // Search by code "K30"
        $responseK30 = $this->getJson('/api/icd10/search?q=K30');
        $responseK30->assertOk();
        $dataK30 = $responseK30->json();
        $this->assertNotEmpty($dataK30);
        $this->assertEquals('K30', $dataK30[0]['code']);

        // Default empty query returns common codes
        $responseDefault = $this->getJson('/api/icd10/search');
        $responseDefault->assertOk();
        $dataDefault = $responseDefault->json();
        $this->assertNotEmpty($dataDefault);
        $this->assertLessThanOrEqual(15, count($dataDefault));
    }
}
