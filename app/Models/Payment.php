<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'rental_id',
        'payment_type',
        'amount',
        'payment_proof_path',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}