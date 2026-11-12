<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nik', 16)->nullable()->unique();
            $table->string('name');
            $table->string('phone')->unique(); // Format E.164 (+628...)
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['MALE', 'FEMALE'])->nullable();
            $table->text('address')->nullable();
            $table->enum('insurance_type', ['NONE', 'BPJS', 'OTHER'])->default('NONE');
            $table->string('insurance_number')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();

            $table->index('phone');
            $table->index('nik');
        });

        Schema::create('clinic_patients', function (Blueprint $table) {
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('medical_record_number')->nullable(); // No. Rekam Medis di klinik tsb
            $table->timestamps();

            $table->primary(['clinic_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patients');
        Schema::dropIfExists('patients');
    }
};
