<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinicA;
    protected Clinic $clinicB;
    protected User $superAdmin;
    protected User $adminUser;
    protected User $staffUser;
    protected User $doctorUser;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinicA = Clinic::create([
            'name' => 'Klinik Sehat Utama',
            'slug' => 'klinik-sehat',
            'phone' => '+62811111111',
            'is_active' => true,
        ]);

        $this->clinicB = Clinic::create([
            'name' => 'Klinik Mitra Mandiri',
            'slug' => 'klinik-mitra',
            'phone' => '+62822222222',
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'dr. Budi Santoso, Sp.PD',
            'specialty' => 'Penyakit Dalam',
            'avg_minutes_per_patient' => 15,
            'is_active' => true,
        ]);

        $this->clinicA->doctors()->attach($this->doctor->id, ['room_number' => 'Ruang 101', 'is_active' => true]);

        // 1. SuperAdmin (no clinic_id)
        $this->superAdmin = User::create([
            'clinic_id' => null,
            'name' => 'Super Administrator',
            'email' => 'super@test.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPERADMIN,
            'is_active' => true,
        ]);

        // 2. Admin Klinik A
        $this->adminUser = User::create([
            'clinic_id' => $this->clinicA->id,
            'name' => 'Admin Klinik A',
            'email' => 'admin@test.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        // 3. Staff Resepsionis Klinik A
        $this->staffUser = User::create([
            'clinic_id' => $this->clinicA->id,
            'name' => 'Staff Resepsionis',
            'email' => 'staff@test.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        // 4. Dokter Klinik A
        $this->doctorUser = User::create([
            'clinic_id' => $this->clinicA->id,
            'doctor_id' => $this->doctor->id,
            'name' => 'dr. Budi Santoso',
            'email' => 'dokter@test.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_DOCTOR,
            'is_active' => true,
        ]);
    }

    /**
     * Test SuperAdmin Permissions
     */
    public function test_superadmin_can_access_everything(): void
    {
        // Multi-clinic management
        $this->actingAs($this->superAdmin)->get('/superadmin/klinik')->assertStatus(200);

        // Admin panel for active clinic
        $this->actingAs($this->superAdmin)->get('/admin/dokter')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/admin/jadwal')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/admin/pengaturan-wa')->assertStatus(200);

        // Receptionist and Doctor portals
        $this->actingAs($this->superAdmin)->get('/antrian')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/dokter/antrian')->assertStatus(200);
    }

    /**
     * Test Clinic Admin Permissions
     */
    public function test_clinic_admin_permissions(): void
    {
        // CANNOT access superadmin panel
        $this->actingAs($this->adminUser)->get('/superadmin/klinik')->assertStatus(403);

        // CAN access admin settings of their clinic
        $this->actingAs($this->adminUser)->get('/admin/dokter')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/admin/jadwal')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/admin/libur')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/admin/pengaturan-wa')->assertStatus(200);

        // CAN access receptionist & doctor views
        $this->actingAs($this->adminUser)->get('/antrian')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/dokter/antrian')->assertStatus(200);
    }

    /**
     * Test Staff (Receptionist) Permissions
     */
    public function test_staff_permissions(): void
    {
        // CANNOT access superadmin panel
        $this->actingAs($this->staffUser)->get('/superadmin/klinik')->assertStatus(403);

        // CANNOT access admin panel
        $this->actingAs($this->staffUser)->get('/admin/dokter')->assertStatus(403);
        $this->actingAs($this->staffUser)->get('/admin/jadwal')->assertStatus(403);
        $this->actingAs($this->staffUser)->get('/admin/libur')->assertStatus(403);
        $this->actingAs($this->staffUser)->get('/admin/pengaturan-wa')->assertStatus(403);

        // CAN access receptionist dashboard
        $this->actingAs($this->staffUser)->get('/antrian')->assertStatus(200);

        // CANNOT access doctor examination portal
        $this->actingAs($this->staffUser)->get('/dokter/antrian')->assertStatus(403);
    }

    /**
     * Test Doctor Permissions
     */
    public function test_doctor_permissions(): void
    {
        // CANNOT access superadmin panel
        $this->actingAs($this->doctorUser)->get('/superadmin/klinik')->assertStatus(403);

        // CANNOT access admin master data & settings
        $this->actingAs($this->doctorUser)->get('/admin/dokter')->assertStatus(403);
        $this->actingAs($this->doctorUser)->get('/admin/jadwal')->assertStatus(403);
        $this->actingAs($this->doctorUser)->get('/admin/libur')->assertStatus(403);
        $this->actingAs($this->doctorUser)->get('/admin/pengaturan-wa')->assertStatus(403);

        // CAN access doctor examination portal
        $this->actingAs($this->doctorUser)->get('/dokter/antrian')->assertStatus(200);

        // Doctor visiting receptionist desk is redirected to doctor examination portal
        $this->actingAs($this->doctorUser)->get('/antrian')->assertRedirect('/dokter/antrian');
    }

    /**
     * Test Navigation Bar visibility per role
     */
    public function test_navigation_bar_visibility_per_role(): void
    {
        // Staff only sees Resepsionis, not Ruang Dokter or Admin/SuperAdmin menus
        $staffRes = $this->actingAs($this->staffUser)->get('/antrian');
        $staffRes->assertSee('Resepsionis');
        $staffRes->assertDontSee('Ruang Dokter');
        $staffRes->assertDontSee('Multi-Klinik');

        // Doctor only sees Ruang Dokter, not Resepsionis or Admin/SuperAdmin menus
        $docRes = $this->actingAs($this->doctorUser)->get('/dokter/antrian');
        $docRes->assertSee('Ruang Dokter');
        $docRes->assertDontSee('Resepsionis');
        $docRes->assertDontSee('Multi-Klinik');

        // Admin sees both Ruang Dokter and Resepsionis and Admin menus, but not Multi-Klinik
        $adminRes = $this->actingAs($this->adminUser)->get('/antrian');
        $adminRes->assertSee('Ruang Dokter');
        $adminRes->assertSee('Resepsionis');
        $adminRes->assertSee('Jadwal');
        $adminRes->assertDontSee('Multi-Klinik');

        // SuperAdmin sees everything including Multi-Klinik
        $superRes = $this->actingAs($this->superAdmin)->get('/antrian');
        $superRes->assertSee('Multi-Klinik');
        $superRes->assertSee('Ruang Dokter');
        $superRes->assertSee('Resepsionis');
    }

    /**
     * Test Public / Guest Access
     */
    public function test_guest_permissions(): void
    {
        // Protected routes redirect to login
        $this->get('/superadmin/klinik')->assertRedirect('/login');
        $this->get('/admin/dokter')->assertRedirect('/login');
        $this->get('/antrian')->assertRedirect('/login');
        $this->get('/dokter/antrian')->assertRedirect('/login');

        // Public Waiting Room Display does NOT require login
        $this->get("/display/{$this->clinicA->slug}")->assertStatus(200);
    }
}

