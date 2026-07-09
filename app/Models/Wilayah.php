<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wilayah extends Model
{
    use SoftDeletes;

    protected $table = 'wilayah';

    protected $fillable = [
        'name',
        'default_radius_km',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_radius_km' => 'decimal:2',
    ];

    public function jastipers()
    {
        return $this->hasMany(Jastiper::class, 'wilayah_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'wilayah_id');
    }
}
