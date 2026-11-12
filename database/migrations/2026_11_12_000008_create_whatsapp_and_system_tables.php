<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_states', function (Blueprint $table) {
            $table->string('phone')->primary(); // nomor pasien
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('step');
            $table->json('context')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('key'); // REGISTER_SUCCESS, REMINDER_H1, NEAR_TURN, CALLED_1, etc.
            $table->text('body');
            $table->timestamps();

            $table->unique(['clinic_id', 'key']);
        });

        Schema::create('message_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('phone');
            $table->enum('direction', ['IN', 'OUT']);
            $table->text('body');
            $table->string('status')->default('SENT'); // QUEUED, SENT, DELIVERED, FAILED
            $table->string('provider_message_id')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'phone']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // QUEUE_CALLED, PATIENT_DELETED, SCHEDULE_OVERRIDDEN, etc.
            $table->string('entity');
            $table->string('entity_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('message_logs');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('conversation_states');
    }
};
