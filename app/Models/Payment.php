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

    /**
     * Mengecek apakah nominal pembayaran valid (harus lebih dari 0).
     */
    public static function isValidAmount(int $amount): bool
    {
        return $amount > 0;
    }

    /**
     * Mengecek apakah ekstensi file bukti pembayaran diizinkan.
     */
    public static function isAllowedProofExtension(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true);
    }

    /**
     * Menghitung total pendapatan dari sekumpulan data pembayaran.
     *
     * @param  iterable<int, self|array{amount: int}>  $payments
     */
    public static function calculateTotalRevenue(iterable $payments): int
    {
        $total = 0;

        foreach ($payments as $payment) {
            $amount = is_array($payment) ? ($payment['amount'] ?? 0) : $payment->amount;
            $total += max(0, (int) $amount);
        }

        return $total;
    }
}