<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Rental untuk mengelola transaksi penyewaan garasi.
 */
class Rental extends Model
{
    protected $fillable = [
        'user_id',
        'garage_id',
        'vehicle_type',
        'plate_number',
        'vehicle_brand_type',
        'battery_capacity_kwh',
        'remaining_battery_kwh',
        'rental_type',
        'duration_days',
        'start_date',
        'end_date',
        'electricity_cost',
        'total_cost',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Mendapatkan pengguna yang melakukan penyewaan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan data garasi yang digunakan dalam penyewaan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Mendapatkan seluruh riwayat pembayaran untuk penyewaan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}