<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = []; // only for assignment

    protected $casts = [
        'passengers' => 'array',
    ];
}
