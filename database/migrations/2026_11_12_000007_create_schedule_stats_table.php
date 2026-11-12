<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('doctor_schedule_id')->constrained('doctor_schedules')->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('patients_served')->default(0);
            $table->unsignedInteger('total_service_minutes')->default(0);
            $table->decimal('avg_minutes', 5, 2)->default(15.00);
            $table->timestamps();

            $table->unique(['doctor_schedule_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_stats');
    }
};
