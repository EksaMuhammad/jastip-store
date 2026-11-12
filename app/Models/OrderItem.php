<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'quantity',
        'hpp_price',
        'markup_amount',
        'estimated_price',
        'actual_price',
        'notes',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function priceAdjustment()
    {
        return $this->hasOne(OrderPriceAdjustment::class, 'order_item_id')->latestOfMany();
    }

    public function getCleanNameAttribute(): string
    {
        if ($this->product && !empty($this->product->name)) {
            return $this->product->name;
        }

        $clean = $this->name ?? 'Item';
        $clean = preg_replace('/^\[.*?\]\s*/', '', $clean);
        $clean = preg_replace('/\s*\[.*?\]$/', '', $clean);

        return trim($clean) ?: ($this->name ?? 'Item');
    }

    public function getMerchantNameAttribute(): ?string
    {
        return $this->product?->merchant?->name ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->product && $this->product->image_url) {
            return $this->product->image_url;
        }

        $img = $this->product?->image;
        if (empty($img) && $this->order && $this->order->reference_photo) {
            $img = $this->order->reference_photo;
        }

        if (empty($img)) {
            return null;
        }

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, 'data:')) {
            return $img;
        }

        if (str_starts_with($img, '/storage/')) {
            return asset(ltrim($img, '/'));
        }

        if (str_starts_with($img, 'storage/')) {
            return asset($img);
        }

        return asset('storage/' . ltrim($img, '/'));
    }
}
