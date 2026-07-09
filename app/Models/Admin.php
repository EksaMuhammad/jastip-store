<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'admins';

    protected $fillable = [
        'email',
        'password_hash',
        'name',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function reviews()
    {
        return $this->hasMany(JastiperVerification::class, 'reviewed_by');
    }

    public function decisions()
    {
        return $this->hasMany(Report::class, 'decided_by');
    }

    public function cancelledOrders()
    {
        return $this->morphMany(Order::class, 'cancelledBy', 'cancelled_by_role', 'cancelled_by_id');
    }
}
