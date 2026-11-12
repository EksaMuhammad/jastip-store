<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartWeightCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_weight_category_detection_and_fare_addition(): void
    {
        $customer = Customer::create([
            'name' => 'Joni Customer',
            'phone_number' => '081234567890',
        ]);

        $merchant = Merchant::create([
            'name' => 'Kopi Kenangan - Sidoarjo',
            'type' => 'food',
            'address' => 'Jl. Jati No. 1, Sidoarjo',
            'latitude' => -7.4520,
            'longitude' => 112.7050,
            'is_active' => true,
        ]);

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Kopi Kenangan Mantan Large',
            'estimated_price' => 24000,
            'is_available' => true,
        ]);

        $cart = Cart::create(['customer_id' => $customer->id]);

        // 1. Uji 2 item -> Ringan (+Rp 0)
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $controller = app(\App\Http\Controllers\Customer\CartController::class);
        $this->assertEquals('ringan', $controller->determineWeightCategory($cart->fresh()));

        // 2. Uji 8 item (seperti pesanan Kopi Kenangan 8 cup) -> Sedang (+Rp 10.000)
        $cartItem->update(['quantity' => 8]);
        $this->assertEquals('sedang', $controller->determineWeightCategory($cart->fresh()));

        // 3. Uji 15 item -> Berat (+Rp 25.000)
        $cartItem->update(['quantity' => 15]);
        $this->assertEquals('berat', $controller->determineWeightCategory($cart->fresh()));
    }

    public function test_checkout_8_items_sets_weight_category_sedang_and_adds_fare(): void
    {
        Wilayah::create([
            'name' => 'Sidoarjo',
            'latitude' => -7.4520,
            'longitude' => 112.7050,
            'default_radius_km' => 15.0,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Joni Customer',
            'phone_number' => '081234567890',
        ]);

        $merchant = Merchant::create([
            'name' => 'Kopi Kenangan - Sidoarjo',
            'type' => 'food',
            'address' => 'Jl. Jati No. 1, Sidoarjo',
            'latitude' => -7.4520,
            'longitude' => 112.7050,
            'is_active' => true,
        ]);

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Kopi Kenangan Mantan Large',
            'estimated_price' => 24000,
            'is_available' => true,
        ]);

        $cart = Cart::create(['customer_id' => $customer->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->post(route('customer.cart.checkout'), [
                'delivery_location' => 'Sedengan Mijen, Sidoarjo',
                'destination_address' => 'Sedengan Mijen, Sidoarjo',
                'destination_lat' => -7.4317,
                'destination_lng' => 112.5959,
                'recipient_name' => 'Joni Customer',
                'recipient_phone' => '081234567890',
            ]);

        $response->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('sedang', $order->weight_category);
        $this->assertGreaterThan(30500, (float) $order->estimated_fare); // Ada tambahan Rp 10.000
    }
}
