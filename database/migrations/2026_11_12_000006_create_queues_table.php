<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignUuid('doctor_schedule_id')->nullable()->constrained('doctor_schedules')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('number');
            $table->string('status')->default('WAITING'); // WAITING, CALLED, IN_SERVICE, SKIPPED, DONE, CANCELLED
            $table->string('source')->default('WHATSAPP'); // WHATSAPP, MANUAL
            $table->unsignedTinyInteger('call_count')->default(0);
            $table->timestamp('last_called_at')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['clinic_id', 'doctor_id', 'queue_date', 'number']);
            $table->index(['clinic_id', 'queue_date', 'status']);
            $table->index(['patient_id', 'queue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};

