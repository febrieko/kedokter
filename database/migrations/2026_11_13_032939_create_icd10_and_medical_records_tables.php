<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Master Data ICD-10
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->string('code', 10)->primary(); // e.g. 'I10', 'A09', 'J00'
            $table->string('name_en');
            $table->string('name_id');
            $table->string('category')->nullable();
            $table->boolean('is_common')->default(false); // Penyakit terbanyak faskes primer
            $table->timestamps();

            $table->index('name_id');
            $table->index('is_common');
        });

        // 2. Encounters (Kunjungan Pasien)
        Schema::create('encounters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignUuid('queue_id')->nullable()->constrained('queues')->nullOnDelete();
            $table->date('encounter_date');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('encounter_type')->default('OUTPATIENT'); // OUTPATIENT, EMERGENCY, TELECONSULTATION
            $table->string('status')->default('IN_PROGRESS'); // IN_PROGRESS, COMPLETED, LOCKED
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'encounter_date']);
            $table->index(['patient_id', 'encounter_date']);
            $table->index(['doctor_id', 'encounter_date']);
        });

        // 3. Medical Records (Catatan Medis SOAP & Tanda Vital)
        Schema::create('medical_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();

            // Subjektif (S)
            $table->text('complaint')->nullable(); // Keluhan utama
            $table->text('history_of_present_illness')->nullable(); // RPS
            $table->text('past_medical_history')->nullable(); // RPD
            $table->text('allergy_history')->nullable(); // Riwayat alergi

            // Objektif (O) & Tanda Vital
            $table->unsignedSmallInteger('systolic')->nullable(); // mmHg
            $table->unsignedSmallInteger('diastolic')->nullable(); // mmHg
            $table->unsignedSmallInteger('heart_rate')->nullable(); // bpm
            $table->unsignedSmallInteger('respiratory_rate')->nullable(); // x/min
            $table->decimal('temperature', 4, 1)->nullable(); // Celcius
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->decimal('height', 5, 1)->nullable(); // cm
            $table->decimal('bmi', 4, 1)->nullable(); // IMT
            $table->text('physical_examination')->nullable(); // Pemeriksaan fisik

            // Asesmen (A) / Diagnosis
            $table->string('primary_icd10_code', 10)->nullable();
            $table->foreign('primary_icd10_code')->references('code')->on('icd10_codes')->nullOnDelete();
            $table->json('secondary_icd10_codes')->nullable(); // array of ICD-10 codes
            $table->text('diagnosis_notes')->nullable(); // Deskripsi bebas diagnosa dokter

            // Plan (P) / Tata Laksana
            $table->text('treatment_plan')->nullable(); // Rencana terapi & edukasi

            $table->timestamps();

            $table->index(['clinic_id', 'patient_id']);
            $table->index('primary_icd10_code');
        });

        // 4. Medical Record Addendums (Catatan Koreksi Pasca Kunci / Audit Trail)
        Schema::create('medical_record_addendums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason'); // Alasan revisi / penambahan
            $table->text('notes'); // Catatan tambahan
            $table->timestamps();

            $table->index('medical_record_id');
        });

        // 5. Prescriptions (Resep Obat)
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('PRESCRIBED'); // PRESCRIBED, DISPENSED, CANCELLED
            $table->timestamps();

            $table->index(['clinic_id', 'encounter_id']);
        });

        // 6. Prescription Items (Item Obat Resep)
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->string('medicine_name');
            $table->string('dosage')->nullable(); // e.g. 500 mg
            $table->string('form')->nullable(); // Tablet, Sirup, Kapsul, Salep
            $table->string('frequency'); // e.g. 3x1 sehari sesudah makan
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('instructions')->nullable();
            $table->timestamps();
        });

        // 7. Clinical Procedures (Tindakan Medis)
        Schema::create('clinical_procedures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('procedure_name');
            $table->string('icd9cm_code')->nullable(); // Kode tindakan medis ICD-9-CM
            $table->text('notes')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['clinic_id', 'encounter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_procedures');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_record_addendums');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('encounters');
        Schema::dropIfExists('icd10_codes');
    }
};
