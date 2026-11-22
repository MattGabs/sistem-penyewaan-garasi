<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}