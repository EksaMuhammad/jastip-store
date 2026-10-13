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
        'estimated_price',
        'discount_price',
        'image',
        'is_available',
        'is_flash_sale'
    ];

    protected $casts = [
        'estimated_price' => 'float',
        'discount_price' => 'float',
        'is_available' => 'boolean',
        'is_flash_sale' => 'boolean',
    ];

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

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset(ltrim($this->image, '/'));
    }
}
