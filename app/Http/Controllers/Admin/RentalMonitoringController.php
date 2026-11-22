<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk mengelola perpanjangan sewa dan laporan penyewaan oleh Admin.
 */
class RentalMonitoringController extends Controller
{
    /**
     * Menampilkan laporan penyewaan berdasarkan periode, tipe garasi,
     * dan penyewa, beserta statistik serta daftar garasi yang sedang disewa.
     */
    public function index(Request $request)
    {
        $query = Rental::with(['user', 'garage', 'payments']);

        // Filter 1: Per bulan dalam tahun tertentu
        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        if ($request->filled('month')) {
            if (! $request->filled('year')) {
                $query->whereYear('start_date', now()->year);
            }
            $query->whereMonth('start_date', $request->month);
        }

        // Filter 2: Per hari dalam bulan tertentu
        if ($request->filled('day')) {
            if (! $request->filled('year')) {
                $query->whereYear('start_date', now()->year);
            }
            if (! $request->filled('month')) {
                $query->whereMonth('start_date', now()->month);
            }
            $query->whereDay('start_date', $request->day);
        }

        // Filter 3: Per Tipe Garasi
        if ($request->filled('garage_type')) {
            $query->whereHas('garage', function ($q) use ($request) {
                $q->where('type', $request->garage_type);
            });
        }

        // Filter 4: Per Penyewa
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $rentals = $query
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 2 END")
            ->latest('start_date')
            ->get();

        // Menampilkan garasi yang masa sewanya masih berlangsung pada hari ini.
        $today = now()->toDateString();
        $activeRentalsToday = Rental::with(['user', 'garage'])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $stats = [
            'total_garages' => Garage::count(),
            'available_garages' => Garage::where('status', 'available')->count(),
            'used_garages' => Garage::where('status', 'used')->count(),
            'filtered_rentals' => $rentals->count(),
            'filtered_revenue' => $rentals->sum('total_cost'),
        ];

        $tenants = User::where('role', 'penyewa')
            ->orderBy('name')
            ->get(['id', 'name', 'nik']);

        return view('admin.rentals.index', compact('stats', 'rentals', 'activeRentalsToday', 'tenants'));
    }

    /**
     * Memperpanjang masa sewa dengan memperbarui tanggal selesai,
     * menghitung biaya tambahan, dan menyimpan bukti pembayaran.
     */
    public function extend(Request $request, Rental $rental)
    {
        if ($rental->status !== 'active') {
            return back()->with('error', 'Transaksi sewa ini sudah tidak aktif dan tidak dapat diperpanjang.');
        }

        $currentEndDate = Carbon::parse($rental->end_date);

        $request->validate([
            'new_end_date' => ['required', 'date', 'after:' . $currentEndDate->toDateString()],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        return DB::transaction(function () use ($request, $rental, $currentEndDate) {
            $garage = $rental->garage;
            $newEndDate = Carbon::parse($request->new_end_date);
            $addedDays = (int) $currentEndDate->diffInDays($newEndDate);
            $addedCost = Rental::calculateExtensionCost($garage->daily_rate, $addedDays);

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