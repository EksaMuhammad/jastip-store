<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'badge_1',
        'badge_2',
        'title',
        'description',
        'terms',
        'button_text',
        'image_path',
        'is_active',
    ];
}
