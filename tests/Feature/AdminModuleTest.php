<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MessageTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected User $adminUser;
    protected User $staffUser;
    protected Doctor $doctor;
    protected DoctorSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::create([
            'name' => 'Klinik Admin Test',
            'slug' => 'klinik-admin',
            'phone' => '+628123456789',
        ]);

        $this->adminUser = User::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Admin Utama',
            'email' => 'admin@test.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->staffUser = User::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Staff Biasa',
            'email' => 'staff@test.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'dr. Wijaya',
            'specialty' => 'Gigi',
            'avg_minutes_per_patient' => 15,
        ]);

        $this->clinic->doctors()->attach($this->doctor->id, ['room_number' => 'R1', 'is_active' => true]);

        $this->schedule = DoctorSchedule::create([
            'clinic_id' => $this->clinic->id,
            'doctor_id' => $this->doctor->id,
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'quota' => 20,
            'is_active' => true,
        ]);
    }

    public function test_staff_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->staffUser)->get('/admin/dokter');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_and_create_doctor(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dokter');
        $response->assertStatus(200);
        $response->assertSee('dr. Wijaya');

        $storeResponse = $this->actingAs($this->adminUser)->post('/admin/dokter', [
            'name' => 'dr. Citra Sp.A',
            'specialty' => 'Anak',
            'sip_number' => 'SIP-999',
            'room_number' => 'Ruang 103',
            'avg_minutes_per_patient' => 20,
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('doctors', ['name' => 'dr. Citra Sp.A']);
    }

    public function test_admin_can_manage_schedules_and_overrides(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/jadwal');
        $response->assertStatus(200);

        // Store dynamic override for next week
        $overrideDate = Carbon::today()->addDays(5)->toDateString();
        $overrideRes = $this->actingAs($this->adminUser)->post('/admin/jadwal/override', [
            'doctor_schedule_id' => $this->schedule->id,
            'override_date' => $overrideDate,
            'quota' => 45, // Custom raised quota
            'reason' => 'Bakti Sosial',
        ]);

        $overrideRes->assertRedirect();
        $this->assertDatabaseHas('schedule_overrides', [
            'doctor_schedule_id' => $this->schedule->id,
            'quota' => 45,
            'reason' => 'Bakti Sosial',
        ]);
    }

    public function test_admin_can_manage_holidays(): void
    {
        $holidayDate = Carbon::today()->addDays(10)->toDateString();

        $response = $this->actingAs($this->adminUser)->post('/admin/libur', [
            'holiday_date' => $holidayDate,
            'reason' => 'Hari Libur Nasional',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('holidays', [
            'clinic_id' => $this->clinic->id,
            'reason' => 'Hari Libur Nasional',
        ]);
    }

    public function test_admin_can_update_whatsapp_settings_and_templates(): void
    {
        $template = MessageTemplate::create([
            'clinic_id' => $this->clinic->id,
            'key' => 'REGISTER_SUCCESS',
            'body' => 'Default Body',
        ]);

        // 1. Update gateway config
        $configRes = $this->actingAs($this->adminUser)->post('/admin/pengaturan-wa/config', [
            'whatsapp_provider' => 'FONNTE',
            'fonnte_token' => 'new_secret_token',
        ]);
        $configRes->assertRedirect();

        $this->assertEquals('FONNTE', $this->clinic->fresh()->whatsapp_provider);

        // 2. Update template
        $tmplRes = $this->actingAs($this->adminUser)->put("/admin/pengaturan-wa/template/{$template->id}", [
            'body' => 'Pendaftaran sukses untuk {nama}!',
        ]);
        $tmplRes->assertRedirect();

        $this->assertEquals('Pendaftaran sukses untuk {nama}!', $template->fresh()->body);
    }
}
