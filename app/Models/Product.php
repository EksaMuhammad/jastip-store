<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'merchant_id',
        'category_id',
        'name',
        'description',
        'hpp_price',
        'discount_hpp_price',
        'is_auto_markup',
        'estimated_price',
        'discount_price',
        'image',
        'is_available',
        'is_flash_sale'
    ];

    protected $casts = [
        'hpp_price' => 'float',
        'discount_hpp_price' => 'float',
        'is_auto_markup' => 'boolean',
        'estimated_price' => 'float',
        'discount_price' => 'float',
        'is_available' => 'boolean',
        'is_flash_sale' => 'boolean',
    ];

    /**
     * Dapatkan harga HPP efektif (modal toko)
     */
    public function getEffectiveHppPriceAttribute(): float
    {
        if ($this->has_discount && !empty($this->discount_hpp_price) && $this->discount_hpp_price > 0) {
            return (float) $this->discount_hpp_price;
        }

        if (!empty($this->hpp_price) && $this->hpp_price > 0) {
            return (float) $this->hpp_price;
        }

        $markup = (float) app(\App\Services\TariffService::class)->get('merchant_markup_percent', 10);
        return round($this->estimated_price / (1 + ($markup / 100)), 2);
    }

    /**
     * Cek apakah produk memiliki harga diskon yang valid (lebih rendah dari harga normal)
     */
    public function getHasDiscountAttribute(): bool
    {
        return !empty($this->discount_price) && $this->discount_price > 0 && $this->discount_price < $this->estimated_price;
    }

    /**
     * Harga final yang harus dibayar customer (harga diskon jika ada, atau harga normal)
     */
    public function getFinalPriceAttribute(): float
    {
        if ($this->has_discount) {
            return (float) $this->discount_price;
        }

        return (float) $this->estimated_price;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->final_price;
    }

    /**
     * Persentase diskon (misal: 25 untuk 25%)
     */
    public function getDiscountPercentageAttribute(): int
    {
        if ($this->has_discount && $this->estimated_price > 0) {
            return (int) round((($this->estimated_price - $this->discount_price) / $this->estimated_price) * 100);
        }

        return 0;
    }

    /**
     * Nominal hemat potongan harga (Rp)
     */
    public function getDiscountAmountAttribute(): float
    {
        if ($this->has_discount) {
            return (float) ($this->estimated_price - $this->discount_price);
        }

        return 0.0;
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * URL foto produk yang valid untuk web asset
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://') || str_starts_with($this->image, 'data:')) {
            return $this->image;
        }

        if (str_starts_with($this->image, '/storage/')) {
            return asset(ltrim($this->image, '/'));
        }

        if (str_starts_with($this->image, 'storage/')) {
            return asset($this->image);
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }
}
