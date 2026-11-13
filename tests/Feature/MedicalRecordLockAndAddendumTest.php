<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\Icd10Code;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAddendum;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MedicalRecordLockAndAddendumTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected Doctor $doctor;
    protected User $doctorUser;
    protected Patient $patient;
    protected Encounter $encounter;
    protected MedicalRecord $medicalRecord;

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

        $this->patient = Patient::create([
            'name' => 'Budi Pratama',
            'nik' => '3201011101900001',
            'phone' => '+6281299990001',
            'insurance_type' => 'BPJS',
            'insurance_number' => '000999888777',
        ]);

        $this->seed(\Database\Seeders\Icd10Seeder::class);

        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 1,
            'status' => Queue::STATUS_DONE,
            'source' => Queue::SOURCE_MANUAL,
            'complaint' => 'Pusing berputar dan mual',
            'doctor_notes' => '[I10] Hipertensi primer',
            'finished_at' => now(),
        ]);

        $this->encounter = Encounter::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'queue_id' => $queue->id,
            'encounter_date' => Carbon::today()->toDateString(),
            'start_time' => now()->subMinutes(20),
            'end_time' => now(),
            'encounter_type' => Encounter::TYPE_OUTPATIENT,
            'status' => Encounter::STATUS_COMPLETED,
            'created_by' => $this->doctorUser->id,
        ]);

        $this->medicalRecord = MedicalRecord::create([
            'encounter_id' => $this->encounter->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'complaint' => 'Pusing berputar dan tengkuk tegang sejak kemarin',
            'history_of_present_illness' => 'Nyeri kepala bagian belakang, tidak disertai demam.',
            'allergy_history' => 'Tidak ada riwayat alergi',
            'systolic' => 150,
            'diastolic' => 95,
            'heart_rate' => 84,
            'respiratory_rate' => 18,
            'temperature' => 36.6,
            'weight' => 70,
            'height' => 170,
            'bmi' => 24.2,
            'physical_examination' => 'Mata: anemis (-), ikterik (-). Cor: S1-S2 murni. Pulmo: vesikuler normal.',
            'primary_icd10_code' => 'I10',
            'diagnosis_notes' => 'Hipertensi Grade 1',
            'treatment_plan' => 'Kurangi konsumsi garam, kontrol ulang 2 minggu lagi.',
        ]);
    }

    public function test_doctor_can_view_medical_record_detail_and_print_page(): void
    {
        // View detail page
        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->get("/dokter/rekam-medis/{$this->encounter->id}");

        $response->assertOk();
        $response->assertSee('Budi Pratama');
        $response->assertSee('150/95');
        $response->assertSee('I10');
        $response->assertSee('Hipertensi');
        $response->assertSee('BELUM DIKUNCI');

        // View print-ready page
        $printRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->get("/dokter/rekam-medis/{$this->encounter->id}/cetak");

        $printRes->assertOk();
        $printRes->assertSee('RESUME RAWAT JALAN');
        $printRes->assertSee('Klinik Pratama Sehat');
    }

    public function test_doctor_can_lock_and_sign_medical_record(): void
    {
        $this->assertEquals(Encounter::STATUS_COMPLETED, $this->encounter->status);
        $this->assertFalse($this->encounter->isLocked());

        // Lock the encounter
        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/rekam-medis/{$this->encounter->id}/lock");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $this->encounter->fresh();
        $this->assertEquals(Encounter::STATUS_LOCKED, $fresh->status);
        $this->assertTrue($fresh->isLocked());
        $this->assertNotNull($fresh->locked_at);
        $this->assertEquals($this->doctorUser->id, $fresh->locked_by);

        // Check view reflects locked status
        $viewRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->get("/dokter/rekam-medis/{$this->encounter->id}");

        $viewRes->assertOk();
        $viewRes->assertSee('TERKUNCI', false);
        $viewRes->assertSee('DITANDATANGANI SECARA DIGITAL', false);
        $viewRes->assertSee('Signed & Valid', false);
    }

    public function test_locked_medical_record_cannot_be_overwritten_via_finish_consultation(): void
    {
        // Lock first
        $this->encounter->lock($this->doctorUser);
        $queue = $this->encounter->queue;

        // Attempt to call finish again to overwrite
        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/antrian/{$queue->id}/finish", [
                'complaint' => 'Hacker attempt to overwrite',
                'primary_icd10_code' => 'K30',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Check medical record remained unchanged
        $freshRecord = $this->medicalRecord->fresh();
        $this->assertEquals('I10', $freshRecord->primary_icd10_code);
        $this->assertEquals('Pusing berputar dan tengkuk tegang sejak kemarin', $freshRecord->complaint);
    }

    public function test_doctor_can_add_addendum_to_locked_medical_record(): void
    {
        // Lock encounter first
        $this->encounter->lock($this->doctorUser);

        // Post addendum
        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/rekam-medis/{$this->encounter->id}/addendum", [
                'reason' => 'Hasil laboratorium darah susulan',
                'notes' => 'Profil lipid: Kolesterol total 240 mg/dL, Trigliserida 180 mg/dL. Ditambahkan terapi Simvastatin 1x20mg malam.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert Addendum exists in database
        $this->assertDatabaseHas('medical_record_addendums', [
            'medical_record_id' => $this->medicalRecord->id,
            'user_id' => $this->doctorUser->id,
            'reason' => 'Hasil laboratorium darah susulan',
        ]);

        $addendum = MedicalRecordAddendum::where('medical_record_id', $this->medicalRecord->id)->first();
        $this->assertNotNull($addendum);
        $this->assertStringContainsString('Simvastatin', $addendum->notes);

        // Check Addendum is displayed in view
        $viewRes = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->get("/dokter/rekam-medis/{$this->encounter->id}");

        $viewRes->assertOk();
        $viewRes->assertSee('Hasil laboratorium darah susulan');
        $viewRes->assertSee('Simvastatin 1x20mg malam');
    }

    public function test_addendum_cannot_be_added_to_unlocked_encounter(): void
    {
        // Encounter is not locked yet
        $this->assertFalse($this->encounter->isLocked());

        $response = $this->actingAs($this->doctorUser)
            ->withSession(['active_clinic_id' => $this->clinic->id])
            ->post("/dokter/rekam-medis/{$this->encounter->id}/addendum", [
                'reason' => 'Tes alasan',
                'notes' => 'Tes catatan',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('medical_record_addendums', [
            'medical_record_id' => $this->medicalRecord->id,
        ]);
    }

    public function test_clinic_scope_blocks_unauthorized_access(): void
    {
        // Create another clinic and doctor
        $otherClinic = Clinic::create([
            'slug' => 'klinik-lain',
            'name' => 'Klinik Medika Sejahtera',
            'is_active' => true,
        ]);

        $otherDoctorUser = User::create([
            'clinic_id' => $otherClinic->id,
            'name' => 'dr. Lain',
            'email' => 'lain@klinik.id',
            'password' => bcrypt('password'),
            'role' => User::ROLE_DOCTOR,
            'is_active' => true,
        ]);

        // Attempt to view encounter from other clinic
        $response = $this->actingAs($otherDoctorUser)
            ->withSession(['active_clinic_id' => $otherClinic->id])
            ->get("/dokter/rekam-medis/{$this->encounter->id}");

        $response->assertForbidden();
    }
}
