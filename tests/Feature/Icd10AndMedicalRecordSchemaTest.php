<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\ClinicalProcedure;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\Icd10Code;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAddendum;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Icd10Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Icd10AndMedicalRecordSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected Doctor $doctor;
    protected Patient $patient;
    protected User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(Icd10Seeder::class);

        $this->clinic = Clinic::create([
            'slug' => 'klinik-sehat',
            'name' => 'Klinik Pratama Sehat',
            'phone' => '+6281234567890',
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'dr. Budi Santoso, Sp.PD',
            'specialty' => 'Penyakit Dalam',
            'sip_number' => 'SIP-12345',
            'avg_minutes_per_patient' => 15,
            'is_active' => true,
        ]);

        $this->clinic->doctors()->attach($this->doctor->id, ['room_number' => 'Ruang 101', 'is_active' => true]);

        $this->patient = Patient::create([
            'name' => 'Ahmad Fauzi',
            'phone' => '+628111222333',
            'nik' => '3171012304850001',
            'insurance_type' => 'BPJS',
            'insurance_number' => '000123456789',
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

    public function test_icd10_codes_are_seeded_and_searchable(): void
    {
        $this->assertGreaterThan(50, Icd10Code::count());

        // Check essential common codes exist
        $this->assertDatabaseHas('icd10_codes', ['code' => 'I10']);
        $this->assertDatabaseHas('icd10_codes', ['code' => 'J00']);
        $this->assertDatabaseHas('icd10_codes', ['code' => 'A09']);
        $this->assertDatabaseHas('icd10_codes', ['code' => 'K30']);
        $this->assertDatabaseHas('icd10_codes', ['code' => 'E11.9']);

        // Test search scope
        $searchResults = Icd10Code::search('Hipertensi')->get();
        $this->assertTrue($searchResults->contains('code', 'I10'));

        $gastritisResults = Icd10Code::search('K29')->get();
        $this->assertTrue($gastritisResults->contains('code', 'K29.7'));
    }

    public function test_full_encounter_and_medical_record_lifecycle(): void
    {
        // 1. Queue created
        $queue = Queue::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'queue_date' => Carbon::today()->toDateString(),
            'number' => 1,
            'status' => Queue::STATUS_IN_SERVICE,
            'complaint' => 'Nyeri perut ulu hati dan kembung',
        ]);

        // 2. Encounter created for this patient visit
        $encounter = Encounter::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'queue_id' => $queue->id,
            'encounter_date' => Carbon::today()->toDateString(),
            'start_time' => now(),
            'encounter_type' => Encounter::TYPE_OUTPATIENT,
            'status' => Encounter::STATUS_IN_PROGRESS,
            'created_by' => $this->doctorUser->id,
        ]);

        $this->assertFalse($encounter->isLocked());
        $this->assertEquals($queue->id, $encounter->queue->id);

        // 3. Medical Record SOAP + Vital Signs created
        $medicalRecord = MedicalRecord::create([
            'encounter_id' => $encounter->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'complaint' => 'Nyeri ulu hati, mual setelah makan, kembung',
            'history_of_present_illness' => 'Keluhan dirasakan sejak 3 hari yang lalu, memberat saat terlambat makan.',
            'systolic' => 120,
            'diastolic' => 80,
            'heart_rate' => 78,
            'respiratory_rate' => 18,
            'temperature' => 36.6,
            'weight' => 65.0,
            'height' => 170.0,
            'physical_examination' => 'Abdomen: Nyeri tekan epigastrium (+), bising usus normal.',
            'primary_icd10_code' => 'K30', // Dispepsia
            'secondary_icd10_codes' => ['K21.9'], // GERD
            'diagnosis_notes' => 'Dispepsia fungsional suspek GERD',
            'treatment_plan' => 'Pola makan teratur, hindari makanan pedas & asam, kurangi kopi.',
        ]);

        $medicalRecord->bmi = $medicalRecord->calculateBmi();
        $medicalRecord->save();

        // Verify BMI calculation: 65 / (1.7 * 1.7) = 22.5
        $this->assertEquals(22.5, $medicalRecord->bmi);
        $this->assertEquals('Dyspepsia', $medicalRecord->primaryIcd10->name_en);
        $this->assertStringContainsString('Dispepsia', $medicalRecord->primaryIcd10->name_id);

        // 4. Prescriptions & Items
        $prescription = Prescription::create([
            'encounter_id' => $encounter->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'notes' => 'Obat lambung 5 hari',
            'status' => Prescription::STATUS_PRESCRIBED,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_name' => 'Omeprazole 20mg',
            'dosage' => '20 mg',
            'form' => 'Kapsul',
            'frequency' => '2 x 1 sehari sebelum makan',
            'quantity' => 10,
            'instructions' => 'Diminum 30 menit sebelum makan pagi & malam',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_name' => 'Antasida Doen Suspensi',
            'dosage' => '100 ml',
            'form' => 'Sirup',
            'frequency' => '3 x 1 sendok takar sebelum makan',
            'quantity' => 1,
            'instructions' => 'Kocok dahulu sebelum diminum bila perih',
        ]);

        $this->assertCount(2, $prescription->items);

        // 5. Clinical Procedure
        ClinicalProcedure::create([
            'encounter_id' => $encounter->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'procedure_name' => 'Pemeriksaan Tanda Vital Lengkap & Palpasi Abdomen',
            'cost' => 25000,
        ]);

        $this->assertCount(1, $encounter->clinicalProcedures);

        // 6. Lock Encounter
        $encounter->update([
            'status' => Encounter::STATUS_LOCKED,
            'end_time' => now(),
            'locked_at' => now(),
            'locked_by' => $this->doctorUser->id,
        ]);

        $this->assertTrue($encounter->fresh()->isLocked());

        // 7. Add Addendum to locked medical record
        $addendum = MedicalRecordAddendum::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => $this->doctorUser->id,
            'reason' => 'Klarifikasi riwayat alergi',
            'notes' => 'Pasien mengonfirmasi riwayat alergi obat golongan penisilin (amoksisilin timbul biduran).',
        ]);

        $this->assertCount(1, $medicalRecord->fresh()->addendums);
        $this->assertEquals('Klarifikasi riwayat alergi', $addendum->reason);
        $this->assertEquals($this->doctorUser->name, $addendum->author->name);
    }
}
