<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('clinic_id')->nullable()->after('id')->constrained('clinics')->nullOnDelete();
            $table->string('role')->default('STAFF')->after('email'); // ADMIN, STAFF, DOCTOR
            $table->foreignUuid('doctor_id')->nullable()->after('role')->constrained('doctors')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropForeign(['doctor_id']);
            $table->dropColumn(['clinic_id', 'role', 'doctor_id', 'is_active']);
        });
    }
};
