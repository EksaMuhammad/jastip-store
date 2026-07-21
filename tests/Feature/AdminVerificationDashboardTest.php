<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Jastiper;
use App\Models\JastiperVerification;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVerificationDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Jastiper $jastiper;
    protected JastiperVerification $verification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
        
        $wilayah = Wilayah::first();
        $this->jastiper = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Joko Jastip',
            'verification_status' => 'menunggu',
            'wilayah_id' => $wilayah->id,
            'radius_km' => 5.00,
            'is_available' => true,
        ]);

        $this->verification = JastiperVerification::create([
            'jastiper_id' => $this->jastiper->id,
            'ktp_image' => 'verifications/ktp/dummy.jpg',
            'selfie_image' => 'verifications/selfie/dummy.jpg',
            'status' => 'menunggu',
        ]);
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@jastipkuy.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.verification'));
        $this->assertAuthenticatedAs($this->admin, 'admin');
    }

    public function test_admin_cannot_login_with_invalid_credentials()
    {
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@jastipkuy.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_admin_dashboard_requires_admin_auth()
    {
        $this->get(route('admin.verification'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_dashboard_renders_successfully()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.verification'))
            ->assertStatus(200)
            ->assertSeeLivewire('admin.admin-verification');
    }

    public function test_admin_can_approve_jastiper_verification()
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test('admin.admin-verification')
            ->call('approveVerification', $this->verification->id)
            ->assertHasNoErrors();

        $this->jastiper->refresh();
        $this->verification->refresh();

        $this->assertEquals('approved', $this->jastiper->verification_status);
        $this->assertEquals('approved', $this->verification->status);
        $this->assertEquals($this->admin->id, $this->verification->reviewed_by);
        $this->assertNotNull($this->verification->reviewed_at);
    }

    public function test_admin_can_reject_jastiper_verification_with_reason()
    {
        $this->actingAs($this->admin, 'admin');

        // Test validation reason is required and min length is 5
        Livewire::test('admin.admin-verification')
            ->set('rejection_reason', 'blur')
            ->call('rejectVerification', $this->verification->id)
            ->assertHasErrors(['rejection_reason' => 'min']);

        // Test successful rejection
        Livewire::test('admin.admin-verification')
            ->set('rejection_reason', 'Foto KTP buram, tidak terbaca')
            ->call('rejectVerification', $this->verification->id)
            ->assertHasNoErrors();

        $this->jastiper->refresh();
        $this->verification->refresh();

        $this->assertEquals('rejected', $this->jastiper->verification_status);
        $this->assertEquals('rejected', $this->verification->status);
        $this->assertEquals('Foto KTP buram, tidak terbaca', $this->verification->rejection_reason);
        $this->assertEquals($this->admin->id, $this->verification->reviewed_by);
        $this->assertNotNull($this->verification->reviewed_at);
    }

    public function test_admin_can_logout()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }
}
