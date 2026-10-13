<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Indomaret Point Tebet',
            'email' => 'indomaret.tebet@example.com',
            'password' => bcrypt('password123'),
            'phone_number' => '081234567890',
            'type' => 'mart',
            'address' => 'Jl. Tebet Raya No. 10',
            'latitude' => -6.2250,
            'longitude' => 106.8550,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_merchant_categories()
    {
        $response = $this->get(route('merchant.categories.index'));
        $response->assertRedirect(route('merchant.login'));
    }

    public function test_merchant_can_view_category_management_page()
    {
        // Global category
        Category::create([
            'name' => 'Makanan',
            'type' => 'mart',
            'merchant_id' => null,
        ]);

        // Merchant custom category
        Category::create([
            'name' => 'Rokok',
            'type' => 'mart',
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->merchant, 'merchant')
            ->get(route('merchant.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Kategori Produk');
        $response->assertSee('Tambah Kategori Baru');
        $response->assertSee('Makanan');
        $response->assertSee('Rokok');
    }

    public function test_merchant_can_store_new_category()
    {
        $response = $this->actingAs($this->merchant, 'merchant')
            ->post(route('merchant.categories.store'), [
                'name' => 'Minuman Dingin',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'name' => 'Minuman Dingin',
            'type' => 'mart',
            'merchant_id' => $this->merchant->id,
        ]);
    }

    public function test_merchant_cannot_store_duplicate_category()
    {
        Category::create([
            'name' => 'Rokok',
            'type' => 'mart',
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->merchant, 'merchant')
            ->post(route('merchant.categories.store'), [
                'name' => 'rokok',
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Category::where('name', 'Rokok')->count());
    }

    public function test_merchant_can_update_own_category()
    {
        $cat = Category::create([
            'name' => 'Rokok',
            'type' => 'mart',
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->merchant, 'merchant')
            ->put(route('merchant.categories.update', $cat), [
                'name' => 'Rokok & Tembakau',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Rokok & Tembakau',
        ]);
    }

    public function test_merchant_cannot_update_global_category()
    {
        $globalCat = Category::create([
            'name' => 'Makanan',
            'type' => 'mart',
            'merchant_id' => null,
        ]);

        $response = $this->actingAs($this->merchant, 'merchant')
            ->put(route('merchant.categories.update', $globalCat), [
                'name' => 'Makanan Ringan',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', [
            'id' => $globalCat->id,
            'name' => 'Makanan',
        ]);
    }

    public function test_merchant_can_delete_own_category()
    {
        $cat = Category::create([
            'name' => 'Vape & Rokok',
            'type' => 'mart',
            'merchant_id' => $this->merchant->id,
        ]);

        $product = Product::create([
            'merchant_id' => $this->merchant->id,
            'category_id' => $cat->id,
            'name' => 'Sampoerna Mild 16',
            'estimated_price' => 35000,
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->merchant, 'merchant')
            ->delete(route('merchant.categories.destroy', $cat));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', [
            'id' => $cat->id,
        ]);

        // Product's category_id should be set to null safely
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => null,
        ]);
    }
}
