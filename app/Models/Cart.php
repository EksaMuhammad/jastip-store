<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'customer_id',
        'merchant_id',
    ];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function catalogItems()
    {
        return $this->hasMany(CartItem::class)->where(function ($q) {
            $q->where('item_type', 'catalog')->whereNotNull('product_id');
        });
    }

    public function customBuyItems()
    {
        return $this->hasMany(CartItem::class)->where('item_type', 'custom_buy');
    }

    public function deliveryServiceItems()
    {
        return $this->hasMany(CartItem::class)->where('item_type', 'delivery_service');
    }

    public function totalItemsPrice(): float
    {
        return (float) $this->cartItems->sum(function ($item) {
            return $item->subtotal;
        });
    }
}
