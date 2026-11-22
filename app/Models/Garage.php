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

    /**
     * Menghasilkan kode unik garasi berdasarkan tipe dan nomor urut (contoh: S001, C001, E001).
     */
    public static function formatCode(string $type, int $number): string
    {
        $prefixMap = [
            'sepeda' => 'S',
            'mobil' => 'C',
            'mobil_listrik' => 'E',
        ];

        $prefix = $prefixMap[$type] ?? throw new \InvalidArgumentException("Tipe garasi tidak valid: {$type}");

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menghitung tarif sewa bulanan (25x tarif harian).
     */
    public function calculateMonthlyRate(): int
    {
        return $this->daily_rate * 25;
    }

    /**
     * Menghitung biaya pengisian listrik hingga penuh khusus garasi mobil listrik.
     */
    public function calculateElectricityCost(float $batteryCapacity, float $remainingBattery): int
    {
        if ($this->type !== 'mobil_listrik' || ! $this->electricity_rate_per_kwh) {
            return 0;
        }

        $kwhNeeded = max(0, $batteryCapacity - $remainingBattery);

        return (int) round($kwhNeeded * $this->electricity_rate_per_kwh);
    }
}