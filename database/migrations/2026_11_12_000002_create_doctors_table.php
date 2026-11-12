<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sip_number')->nullable();
            $table->string('specialty')->default('Umum');
            $table->unsignedInteger('avg_minutes_per_patient')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinic_doctors', function (Blueprint $table) {
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('room_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary(['clinic_id', 'doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_doctors');
        Schema::dropIfExists('doctors');
    }
};
