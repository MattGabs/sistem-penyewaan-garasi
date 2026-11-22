<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Garage untuk mengelola data dan informasi garasi.
 */
class Garage extends Model
{
    protected $fillable = [
        'code',
        'type',
        'daily_rate',
        'electricity_rate_per_kwh',
        'status',
    ];

    /**
     * Mendapatkan seluruh transaksi rental yang menggunakan garasi ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}