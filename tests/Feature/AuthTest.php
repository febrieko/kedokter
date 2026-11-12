<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected Clinic $clinic;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::create([
            'name' => 'Klinik Pratama Sehat',
            'slug' => 'klinik-sehat',
            'phone' => '+628123456789',
        ]);

        $this->user = User::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Siti Resepsionis',
            'email' => 'resepsionis@klinik.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_visiting_dashboard(): void
    {
        $response = $this->get('/antrian');
        $response->assertRedirect('/login');

        $rootResponse = $this->get('/');
        $rootResponse->assertRedirect('/login');
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Dashboard');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $response = $this->post('/login', [
            'email' => 'resepsionis@klinik.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->user);
        $response->assertRedirect('/antrian');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'resepsionis@klinik.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_logout(): void
    {
        $response = $this->actingAs($this->user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_waiting_room_display_does_not_require_login(): void
    {
        $response = $this->get("/display/{$this->clinic->slug}");
        $response->assertStatus(200);
        $response->assertSee('Live Antrian Ruang Tunggu');
    }
}

