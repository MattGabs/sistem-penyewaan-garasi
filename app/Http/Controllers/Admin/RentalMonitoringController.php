<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk memantau dan mengelola transaksi penyewaan oleh Admin.
 */
class RentalMonitoringController extends Controller
{
    /**
     * Menampilkan statistik garasi, total pendapatan,
     * dan daftar seluruh transaksi penyewaan.
     */
    public function index()
    {
        $stats = [
            'total_garages' => Garage::count(),
            'available_garages' => Garage::where('status', 'available')->count(),
            'used_garages' => Garage::where('status', 'used')->count(),
            'active_rentals' => Rental::where('status', 'active')->count(),
            'total_revenue' => Payment::sum('amount'),
        ];

        $rentals = Rental::with(['user', 'garage', 'payments'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        return view('admin.rentals.index', compact('stats', 'rentals'));
    }

    /**
     * Memproses perpanjangan masa sewa oleh Admin,
     * menghitung biaya tambahan, dan menyimpan bukti pembayaran.
     */
    public function extend(Request $request, Rental $rental)
    {
        if ($rental->status !== 'active') {
            return back()->with('error', 'Transaksi sewa ini sudah selesai dan tidak dapat diperpanjang.');
        }

        $request->validate([
            'rental_type' => ['required', 'in:harian,bulanan'],
            'duration' => ['required', 'integer', 'min:1', 'max:365'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        return DB::transaction(function () use ($request, $rental) {
            $garage = $rental->garage;
            $currentEndDate = Carbon::parse($rental->end_date);
            $duration = (int) $request->duration;

            if ($request->rental_type === 'harian') {
                $addedDays = $duration;
                $newEndDate = $currentEndDate->copy()->addDays($addedDays);
                $addedCost = $garage->daily_rate * $addedDays;
            } else {
                $newEndDate = $currentEndDate->copy()->addMonthsNoOverflow($duration);
                $addedDays = (int) $currentEndDate->diffInDays($newEndDate);
                $addedCost = ($garage->daily_rate * 25) * $duration;
            }

            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            $rental->update([
                'duration_days' => $rental->duration_days + $addedDays,
                'end_date' => $newEndDate->toDateString(),
                'total_cost' => $rental->total_cost + $addedCost,
            ]);

            Payment::create([
                'rental_id' => $rental->id,
                'payment_type' => 'extension',
                'amount' => $addedCost,
                'payment_proof_path' => $proofPath,
                'payment_date' => now()->toDateString(),
            ]);

            return back()->with('success', "Sewa garasi {$garage->code} berhasil diperpanjang sampai {$newEndDate->format('d M Y')}.");
        });
    }
}