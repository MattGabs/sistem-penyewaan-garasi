<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Payment untuk mengelola riwayat pembayaran penyewaan.
 */
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

    /**
     * Mendapatkan transaksi rental yang terkait dengan pembayaran.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}