<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFavorite extends Model
{
    protected $table = 'customer_favorites';

    protected $fillable = [
        'customer_id',
        'jastiper_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function jastiper()
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }
}
