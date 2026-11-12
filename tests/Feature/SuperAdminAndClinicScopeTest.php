<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminAndClinicScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinicA;
    protected Clinic $clinicB;
    protected User $superAdmin;
    protected User $clinicAdminA;
    protected User $clinicStaffA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinicA = Clinic::create([
            'name' => 'Klinik A Sehat',
            'slug' => 'klinik-a',
            'phone' => '+62811111111',
            'is_active' => true,
        ]);

        $this->clinicB = Clinic::create([
            'name' => 'Klinik B Prima',
            'slug' => 'klinik-b',
            'phone' => '+62822222222',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'clinic_id' => null,
            'name' => 'Super Admin Kedokter',
            'email' => 'super@kedokter.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPERADMIN,
            'is_active' => true,
        ]);

        $this->clinicAdminA = User::create([
            'clinic_id' => $this->clinicA->id,
            'name' => 'Admin Klinik A',
            'email' => 'admin.a@klinik.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->clinicStaffA = User::create([
            'clinic_id' => $this->clinicA->id,
            'name' => 'Staff Klinik A',
            'email' => 'staff.a@klinik.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    public function test_non_superadmin_cannot_access_superadmin_panel(): void
    {
        $response = $this->actingAs($this->clinicAdminA)->get('/superadmin/klinik');
        $response->assertStatus(403);

        $responseStaff = $this->actingAs($this->clinicStaffA)->get('/superadmin/klinik');
        $responseStaff->assertStatus(403);
    }

    public function test_superadmin_can_view_and_create_new_clinic(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/superadmin/klinik');
        $response->assertStatus(200);
        $response->assertSee('Klinik A Sehat');
        $response->assertSee('Klinik B Prima');

        // Create new clinic C with an initial admin
        $createRes = $this->actingAs($this->superAdmin)->post('/superadmin/klinik', [
            'name' => 'Klinik C Sentosa',
            'slug' => 'klinik-c',
            'phone' => '+62833333333',
            'address' => 'Jl. Merdeka No. 10',
            'timezone' => 'Asia/Jakarta',
            'admin_name' => 'Admin Sentosa',
            'admin_email' => 'admin@sentosa.id',
            'admin_password' => 'secret123',
        ]);

        $createRes->assertRedirect();
        $this->assertDatabaseHas('clinics', ['slug' => 'klinik-c']);
        $this->assertDatabaseHas('users', ['email' => 'admin@sentosa.id', 'role' => User::ROLE_ADMIN]);
    }

    public function test_superadmin_can_switch_active_clinic(): void
    {
        $response = $this->actingAs($this->superAdmin)->post("/superadmin/switch/{$this->clinicB->id}");

        $response->assertRedirect();
        $response->assertSessionHas('active_clinic_id', $this->clinicB->id);
    }

    public function test_clinic_scope_middleware_locks_admin_to_their_own_clinic(): void
    {
        $response = $this->actingAs($this->clinicAdminA)->get('/antrian');
        $response->assertStatus(200);
        $response->assertSee('Klinik A Sehat');
    }

    public function test_inactive_clinic_user_is_blocked(): void
    {
        $this->clinicA->update(['is_active' => false]);

        $response = $this->actingAs($this->clinicAdminA)->get('/antrian');
        $response->assertStatus(403);
    }
}
