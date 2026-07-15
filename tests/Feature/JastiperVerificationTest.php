<?php

namespace Tests\Feature;

use App\Models\Jastiper;
use App\Models\JastiperVerification;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class JastiperVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Jastiper $jastiper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $wilayah = Wilayah::first();

        $this->jastiper = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Joko Widodo',
            'verification_status' => 'belum',
            'wilayah_id' => $wilayah->id,
            'radius_km' => 5.00,
            'is_available' => true,
        ]);
    }

    public function test_verification_page_requires_jastiper_auth()
    {
        $this->get(route('jastiper.verification'))
            ->assertRedirect(route('login'));
    }

    public function test_verification_page_renders_successfully()
    {
        $this->actingAs($this->jastiper, 'jastiper')
            ->get(route('jastiper.verification'))
            ->assertStatus(200)
            ->assertSeeLivewire('jastiper.jastiper-verification-form');
    }

    public function test_jastiper_can_submit_verification_documents()
    {
        Storage::fake('public');

        $ktpFile = UploadedFile::fake()->image('ktp.jpg');
        $selfieFile = UploadedFile::fake()->image('selfie.jpg');

        $this->actingAs($this->jastiper, 'jastiper');

        Livewire::test('jastiper.jastiper-verification-form')
            ->set('ktp_image', $ktpFile)
            ->set('selfie_image', $selfieFile)
            ->call('submitVerification')
            ->assertRedirect(route('jastiper.verification'));

        // Check verification record in DB
        $this->assertDatabaseHas('jastiper_verifications', [
            'jastiper_id' => $this->jastiper->id,
            'status' => 'menunggu',
        ]);

        $this->jastiper->refresh();
        $this->assertEquals('menunggu', $this->jastiper->verification_status);

        // Check if files are stored
        $latest = JastiperVerification::latest()->first();
        Storage::disk('public')->assertExists($latest->ktp_image);
        Storage::disk('public')->assertExists($latest->selfie_image);
    }

    public function test_admin_can_simulate_approval()
    {
        $verification = JastiperVerification::create([
            'jastiper_id' => $this->jastiper->id,
            'ktp_image' => 'verifications/ktp/dummy.jpg',
            'selfie_image' => 'verifications/selfie/dummy.jpg',
            'status' => 'menunggu',
        ]);

        $this->jastiper->verification_status = 'menunggu';
        $this->jastiper->save();

        $this->actingAs($this->jastiper, 'jastiper');

        Livewire::test('jastiper.jastiper-verification-form')
            ->call('adminSimulateStatus', 'approved')
            ->assertRedirect(route('jastiper.verification'));

        $this->jastiper->refresh();
        $this->assertEquals('approved', $this->jastiper->verification_status);

        $verification->refresh();
        $this->assertEquals('approved', $verification->status);
    }

    public function test_admin_can_simulate_rejection_and_jastiper_can_resubmit()
    {
        $verification = JastiperVerification::create([
            'jastiper_id' => $this->jastiper->id,
            'ktp_image' => 'verifications/ktp/dummy.jpg',
            'selfie_image' => 'verifications/selfie/dummy.jpg',
            'status' => 'menunggu',
        ]);

        $this->jastiper->verification_status = 'menunggu';
        $this->jastiper->save();

        $this->actingAs($this->jastiper, 'jastiper');

        Livewire::test('jastiper.jastiper-verification-form')
            ->call('adminSimulateStatus', 'rejected', 'KTP buram')
            ->assertRedirect(route('jastiper.verification'));

        $this->jastiper->refresh();
        $this->assertEquals('rejected', $this->jastiper->verification_status);

        $verification->refresh();
        $this->assertEquals('rejected', $verification->status);
        $this->assertEquals('KTP buram', $verification->rejection_reason);

        // Test resubmission
        Livewire::test('jastiper.jastiper-verification-form')
            ->call('triggerResubmission')
            ->assertRedirect(route('jastiper.verification'));

        $this->jastiper->refresh();
        $this->assertEquals('belum', $this->jastiper->verification_status);
    }
}
