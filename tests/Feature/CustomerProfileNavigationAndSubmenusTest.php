<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerProfileNavigationAndSubmenusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_customer_profile_contains_all_menu_links()
    {
        $customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Customer',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('customer.profile'));

        $response->assertStatus(200);
        $response->assertSee(route('customer.security'));
        $response->assertSee(route('customer.wallet'));
        $response->assertSee(route('customer.address'));
        $response->assertSee(route('customer.booking', ['tab' => 'favorites']));
        $response->assertSee(route('faq'));
        $response->assertSee(route('terms'));
        $response->assertSee(route('privacy'));
    }

    public function test_wallet_page_has_back_button_to_profile()
    {
        $customer = Customer::create([
            'phone_number' => '081234567891',
            'name' => 'Siti Customer',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('customer.wallet'));

        $response->assertStatus(200);
        $response->assertSee(route('customer.profile'));
        $response->assertSee('Kembali ke Profil');
    }

    public function test_customer_address_page_renders_and_has_back_button()
    {
        $customer = Customer::create([
            'phone_number' => '081234567892',
            'name' => 'Rudi Customer',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('customer.address'));

        $response->assertStatus(200);
        $response->assertSee(route('customer.profile'));
        $response->assertSee('Tambah Alamat');
        $response->assertSee('Simpan Alamat');
    }

    public function test_customer_can_save_address()
    {
        $customer = Customer::create([
            'phone_number' => '081234567893',
            'name' => 'Dewi Customer',
            'password' => bcrypt('password123'),
        ]);

        $postData = [
            'label' => 'Rumah Utama',
            'address' => 'Jl. Ijen No. 12, Malang, Jawa Timur',
            'latitude' => -7.978645,
            'longitude' => 112.628892,
            'notes' => 'Pagar putih, rumah cat abu-abu',
        ];

        $response = $this->actingAs($customer, 'customer')
                         ->post(route('customer.address.save'), $postData);

        $response->assertRedirect(route('customer.address'));
        $response->assertSessionHas('success', 'Alamat berhasil disimpan!');

        // Follow redirect to see saved address
        $follow = $this->actingAs($customer, 'customer')->get(route('customer.address'));
        $follow->assertSee('Rumah Utama');
        $follow->assertSee('Jl. Ijen No. 12, Malang, Jawa Timur');
    }

    public function test_customer_can_save_multiple_addresses_and_delete_address()
    {
        $customer = Customer::create([
            'phone_number' => '081234567899',
            'name' => 'Multi Address Customer',
            'password' => bcrypt('password123'),
        ]);

        // Simpan alamat 1 (Rumah)
        $this->actingAs($customer, 'customer')->post(route('customer.address.save'), [
            'label' => 'Rumah Sidoarjo',
            'address' => 'Perum TAS 4 Cluster Gardenia Sidoarjo',
            'latitude' => -7.4478,
            'longitude' => 112.7183,
        ]);

        // Simpan alamat 2 (Kantor)
        $this->actingAs($customer, 'customer')->post(route('customer.address.save'), [
            'label' => 'Kantor Surabaya',
            'address' => 'Jl. Basuki Rahmat No. 10, Surabaya',
            'latitude' => -7.2654,
            'longitude' => 112.7423,
        ]);

        $page = $this->actingAs($customer, 'customer')->get(route('customer.address'));
        $page->assertSee('Rumah Sidoarjo');
        $page->assertSee('Kantor Surabaya');

        // Ambil saved addresses dari controller helper
        $addresses = \App\Http\Controllers\DashboardController::getCustomerSavedAddresses($customer);
        $this->assertCount(2, $addresses);

        // Hapus alamat pertama
        $deleteId = $addresses[0]['id'];
        $deleteResp = $this->actingAs($customer, 'customer')->post(route('customer.address.delete', $deleteId));
        $deleteResp->assertRedirect(route('customer.address'));

        $addressesAfter = \App\Http\Controllers\DashboardController::getCustomerSavedAddresses($customer);
        $this->assertCount(1, $addressesAfter);
    }

    public function test_customer_security_page_for_otp_only_user_shows_default_password_option()
    {
        $customer = Customer::create([
            'phone_number' => '081234567894',
            'name' => 'OTP Customer',
            'password' => null, // registered via OTP WhatsApp
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('customer.security'));

        $response->assertStatus(200);
        $response->assertSee(route('customer.profile'));
        $response->assertSee('Login Mode OTP WhatsApp');
        $response->assertSee('Kata Sandi Default');
        $response->assertSee('password123');
        $response->assertDontSee('Kata Sandi Saat Ini'); // No current password field needed
    }

    public function test_otp_customer_can_set_default_password()
    {
        $customer = Customer::create([
            'phone_number' => '081234567895',
            'name' => 'OTP Customer 2',
            'password' => null,
        ]);

        $response = $this->actingAs($customer, 'customer')->put(route('customer.security.password'), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('customer.security'));
        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertNotNull($customer->password);
        $this->assertTrue(Hash::check('password123', $customer->password));
    }

    public function test_customer_with_password_requires_current_password_to_change()
    {
        $customer = Customer::create([
            'phone_number' => '081234567896',
            'name' => 'Secure Customer',
            'password' => Hash::make('oldpassword123'),
        ]);

        // Wrong current password
        $response = $this->actingAs($customer, 'customer')->put(route('customer.security.password'), [
            'current_password' => 'wrongpass',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors(['current_password']);

        // Correct current password
        $responseSuccess = $this->actingAs($customer, 'customer')->put(route('customer.security.password'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $responseSuccess->assertRedirect(route('customer.security'));
        $customer->refresh();
        $this->assertTrue(Hash::check('newpassword456', $customer->password));
    }

    public function test_all_profile_subpages_have_back_button_to_profile()
    {
        $customer = Customer::create([
            'phone_number' => '081234567897',
            'name' => 'Test Profile Back',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($customer, 'customer');

        // FAQ
        $resFaq = $this->get(route('faq'));
        $resFaq->assertStatus(200);
        $resFaq->assertSee(route('customer.profile'));

        // Terms
        $resTerms = $this->get(route('terms'));
        $resTerms->assertStatus(200);
        $resTerms->assertSee(route('customer.profile'));

        // Privacy
        $resPrivacy = $this->get(route('privacy'));
        $resPrivacy->assertStatus(200);
        $resPrivacy->assertSee(route('customer.profile'));

        // Booking
        $resBooking = $this->get(route('customer.booking', ['tab' => 'favorites']));
        $resBooking->assertStatus(200);
        $resBooking->assertSee(route('customer.profile'));
    }

    public function test_order_form_supports_saved_address_selection_and_hides_raw_coordinates()
    {
        $customer = Customer::create([
            'phone_number' => '081234567888',
            'name' => 'Saved Address Orderer',
            'password' => Hash::make('password123'),
        ]);

        // Simpan 2 alamat tersimpan
        \App\Http\Controllers\DashboardController::persistCustomerAddresses($customer, [
            [
                'id' => 'addr_rumah_123',
                'label' => 'Rumah Cantik',
                'address' => 'Perumahan Asri Indah Blok A1, Sidoarjo',
                'latitude' => -7.4478,
                'longitude' => 112.7183,
                'notes' => 'Pagar abu-abu',
            ],
            [
                'id' => 'addr_kantor_456',
                'label' => 'Kantor Pusat',
                'address' => 'Jl. Pemuda No. 45, Surabaya',
                'latitude' => -7.2654,
                'longitude' => 112.7423,
                'notes' => 'Lantai 3',
            ],
        ]);

        $this->actingAs($customer, 'customer');

        $comp = Livewire::test('customer.create-order-form');

        // Pastikan saved addresses ter-load
        $comp->assertSet('saved_addresses', function ($addrs) {
            return count($addrs) === 2 && $addrs[0]['label'] === 'Rumah Cantik';
        });

        // Pilih alamat tersimpan via method
        $comp->call('selectSavedAddress', 'addr_rumah_123');
        $comp->assertSet('destination_address', 'Perumahan Asri Indah Blok A1, Sidoarjo');
        $comp->assertSet('destination_lat', -7.4478);
        $comp->assertSet('destination_lng', 112.7183);

        // Pastikan UI menampilkan opsi "Lokasi Saya (GPS)" dan "Alamat Tersimpan"
        $comp->assertSee('Lokasi Saya (GPS)');
        $comp->assertSee('Alamat Tersimpan');
        $comp->assertSee('Rumah Cantik');

        // Pastikan UI TIDAK menampilkan koordinat teknis ke customer
        $comp->assertDontSee('📍 Koordinat Asal:');
        $comp->assertDontSee('📍 Koordinat Tujuan:');
        $comp->assertDontSee('📍 Koordinat Stop:');
    }
}

