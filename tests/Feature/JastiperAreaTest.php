<?php

namespace Tests\Feature;

use App\Models\Jastiper;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JastiperAreaTest extends TestCase
{
    use RefreshDatabase;

    protected Jastiper $jastiper;
    protected Wilayah $wilayah1;
    protected Wilayah $wilayah2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->wilayah1 = Wilayah::first();
        $this->wilayah2 = Wilayah::create([
            'name' => 'Surabaya Kota',
            'default_radius_km' => 3.00,
            'is_active' => true,
        ]);

        $this->jastiper = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Joko Widodo',
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah1->id,
            'radius_km' => 5.00,
            'current_lat' => -7.9839,
            'current_lng' => 112.6214,
            'is_available' => true,
        ]);
    }

    public function test_area_settings_page_requires_jastiper_auth()
    {
        $this->get(route('jastiper.area'))
            ->assertRedirect(route('login'));
    }

    public function test_area_settings_page_renders_successfully()
    {
        $this->actingAs($this->jastiper, 'jastiper')
            ->get(route('jastiper.area'))
            ->assertStatus(200)
            ->assertSeeLivewire('jastiper.jastiper-area-settings');
    }

    public function test_jastiper_can_save_wilayah_and_radius()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        Livewire::test('jastiper.jastiper-area-settings')
            ->set('wilayah_id', $this->wilayah2->id)
            ->set('radius_km', 7.5)
            ->set('current_lat', -7.2504) // Surabaya lat
            ->set('current_lng', 112.7508) // Surabaya lng
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertSet('success_message', 'Pengaturan wilayah & radius jangkauan berhasil disimpan!');

        $this->jastiper->refresh();
        $this->assertEquals($this->wilayah2->id, $this->jastiper->wilayah_id);
        $this->assertEquals(7.50, floatval($this->jastiper->radius_km));
        $this->assertEquals(-7.2504, floatval($this->jastiper->current_lat));
        $this->assertEquals(112.7508, floatval($this->jastiper->current_lng));
    }

    public function test_jastiper_validation_rules_on_saving_area()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        Livewire::test('jastiper.jastiper-area-settings')
            ->set('wilayah_id', '')
            ->set('radius_km', 20) // Max is 15
            ->call('saveSettings')
            ->assertHasErrors(['wilayah_id' => 'required', 'radius_km' => 'max']);
    }

    public function test_gps_simulation_updates_coordinates_realtime()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        // Initially not simulating
        $component = Livewire::test('jastiper.jastiper-area-settings')
            ->assertSet('is_simulating', false);

        // Turn on simulation
        $component->call('toggleSimulation')
            ->assertSet('is_simulating', true);

        // Simulate moving coordinates
        $component->call('updateLocation', -7.9850, 112.6220);

        $this->jastiper->refresh();
        $this->assertEquals(-7.9850, floatval($this->jastiper->current_lat));
        $this->assertEquals(112.6220, floatval($this->jastiper->current_lng));

        // Turn off simulation
        $component->call('toggleSimulation')
            ->assertSet('is_simulating', false);
    }
}
