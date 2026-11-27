<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;  

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
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
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

    /**
     * Menghitung tanggal selesai sewa dan total durasi hari berdasarkan paket (harian/bulanan).
     *
     * @return array{end_date: string, duration_days: int}
     */
    public static function calculateSchedule(string $startDate, string $rentalType, int $duration): array
    {
        $start = Carbon::parse($startDate);

        if ($rentalType === 'harian') {
            $end = $start->copy()->addDays($duration);
            $days = $duration;
        } else {
            $end = $start->copy()->addMonthsNoOverflow($duration);
            $days = (int) $start->diffInDays($end);
        }

        return [
            'end_date' => $end->toDateString(),
            'duration_days' => $days,
        ];
    }

    /**
     * Menghitung total biaya sewa (biaya sewa garasi + biaya listrik).
     */
    public static function calculateTotalCost(int $dailyRate, string $rentalType, int $duration, int $electricityCost = 0): int
    {
        $rentCost = $rentalType === 'harian'
            ? $dailyRate * $duration
            : ($dailyRate * 25) * $duration;

        return $rentCost + $electricityCost;
    }
    
    /**
     * Menghitung sisa biaya perpanjangan sewa berdasarkan tambahan hari.
     * Setiap kelipatan 30 hari dihitung tarif bulanan (25x harian), sisanya tarif harian.
     */
    public static function calculateExtensionCost(int $dailyRate, int$addedDays): int
    {
        $months = intdiv($addedDays, 30);
        $remainingDays =$addedDays % 30;

        return ($months * ($dailyRate * 25)) + ($remainingDays * $dailyRate);
    }
}