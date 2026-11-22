<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $fillable = [
        'code',
        'type',
        'daily_rate',
        'electricity_rate_per_kwh',
        'status',
    ];

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}