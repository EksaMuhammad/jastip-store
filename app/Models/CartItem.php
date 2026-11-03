<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'merchant_id',
        'quantity',
        'notes',
        'item_type',
        'category',
        'name',
        'description',
        'estimated_price',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'recipient_name',
        'recipient_phone',
        'reference_photo',
        'weight_category',
        'courier_agency',
        'stops_data',
    ];

    protected $casts = [
        'estimated_price' => 'float',
        'origin_lat' => 'float',
        'origin_lng' => 'float',
        'destination_lat' => 'float',
        'destination_lng' => 'float',
        'stops_data' => 'array',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isCatalog(): bool
    {
        return $this->item_type === 'catalog' && !empty($this->product_id);
    }

    public function isCustomBuy(): bool
    {
        return $this->item_type === 'custom_buy';
    }

    public function isDeliveryService(): bool
    {
        return $this->item_type === 'delivery_service';
    }

    public function getItemTitleAttribute(): string
    {
        if ($this->isCatalog() && $this->product) {
            return $this->product->name;
        }

        return $this->name ?: ($this->description ? \Illuminate\Support\Str::limit($this->description, 40) : 'Layanan Jastip');
    }

    public function getItemPriceAttribute(): float
    {
        if ($this->isCatalog() && $this->product) {
            return (float) $this->product->final_price;
        }

        return (float) ($this->estimated_price ?? 0);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->item_price * max(1, $this->quantity);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->isCatalog() && $this->product) {
            return $this->product->image_url;
        }

        if ($this->reference_photo) {
            return asset('storage/' . $this->reference_photo);
        }

        return null;
    }
}
