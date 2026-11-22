<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk mengelola proses penyewaan garasi oleh penyewa.
 */
class RentalController extends Controller
{
    /**
     * Menampilkan riwayat penyewaan milik pengguna yang sedang login.
     */
    public function index(Request $request)
    {
        $rentals = Rental::with(['garage', 'payments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('tenant.rentals.index', compact('rentals'));
    }

    /**
     * Menampilkan formulir penyewaan beserta ringkasan garasi tersedia.
     *
     * Hanya penyewa dengan status verifikasi approved yang
     * diperbolehkan mengakses formulir penyewaan.
     */
    public function create(Request $request)
    {
        if ($request->user()->verification_status !== 'approved') {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Akun Anda harus diverifikasi Admin terlebih dahulu sebelum menyewa garasi.');
        }

        $garageSummary = Garage::where('status', 'available')
            ->selectRaw('type, COUNT(*) as available_count, MIN(daily_rate) as daily_rate, MIN(electricity_rate_per_kwh) as electricity_rate')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return view('tenant.rentals.create', compact('garageSummary'));
    }

    /**
     * Memvalidasi dan memproses permintaan penyewaan garasi.
     *
     * Menghitung biaya sewa dan listrik, menyimpan data rental
     * beserta bukti pembayaran, kemudian memperbarui status garasi.
     */
    public function store(Request $request)
    {
        if ($request->user()->verification_status !== 'approved') {
            abort(403, 'Akun Anda belum diverifikasi oleh Admin.');
        }

        $request->validate([
            'vehicle_type' => ['required', 'in:sepeda,sepeda_motor,mobil,mobil_listrik'],
            'plate_number' => ['required_unless:vehicle_type,sepeda', 'nullable', 'string', 'max:15'],
            'vehicle_brand_type' => ['required', 'string', 'max:100'],
            'battery_capacity_kwh' => ['required_if:vehicle_type,mobil_listrik', 'nullable', 'numeric', 'min:1', 'max:999999.99'],
            'remaining_battery_kwh' => ['required_if:vehicle_type,mobil_listrik', 'nullable', 'numeric', 'min:0', 'lte:battery_capacity_kwh'],
            'rental_type' => ['required', 'in:harian,bulanan'],
            'duration' => ['required', 'integer', 'min:1', 'max:365'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $garageTypeMap = [
            'sepeda' => 'sepeda',
            'sepeda_motor' => 'sepeda',
            'mobil' => 'mobil',
            'mobil_listrik' => 'mobil_listrik',
        ];

        $targetGarageType = $garageTypeMap[$request->vehicle_type];

        return DB::transaction(function () use ($request, $targetGarageType) {
            $garage = Garage::where('type', $targetGarageType)
                ->where('status', 'available')
                ->orderBy('code', 'asc')
                ->lockForUpdate()
                ->first();

            if (! $garage) {
                return back()
                    ->withErrors(['vehicle_type' => 'Maaf, unit garasi untuk tipe kendaraan ini sedang penuh.'])
                    ->withInput();
            }

            $startDate = Carbon::parse($request->start_date);
            $duration = (int) $request->duration;

            if ($request->rental_type === 'harian') {
                $durationDays = $duration;
                $endDate = $startDate->copy()->addDays($durationDays);
                $rentCost = $garage->daily_rate * $durationDays;
            } else {
                $endDate = $startDate->copy()->addMonthsNoOverflow($duration);
                $durationDays = (int) $startDate->diffInDays($endDate);
                $rentCost = ($garage->daily_rate * 25) * $duration;
            }

            $electricityCost = 0;
            if ($request->vehicle_type === 'mobil_listrik') {
                $kwhNeeded = max(0, (float) $request->battery_capacity_kwh - (float) $request->remaining_battery_kwh);
                $electricityCost = (int) round($kwhNeeded * $garage->electricity_rate_per_kwh);
            }

            $totalCost = $rentCost + $electricityCost;
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            $rental = Rental::create([
                'user_id' => $request->user()->id,
                'garage_id' => $garage->id,
                'vehicle_type' => $request->vehicle_type,
                'plate_number' => $request->vehicle_type === 'sepeda' ? null : strtoupper($request->plate_number),
                'vehicle_brand_type' => $request->vehicle_brand_type,
                'battery_capacity_kwh' => $request->vehicle_type === 'mobil_listrik' ? $request->battery_capacity_kwh : null,
                'remaining_battery_kwh' => $request->vehicle_type === 'mobil_listrik' ? $request->remaining_battery_kwh : null,
                'rental_type' => $request->rental_type,
                'duration_days' => $durationDays,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'electricity_cost' => $electricityCost,
                'total_cost' => $totalCost,
                'status' => 'active',
            ]);

            Payment::create([
                'rental_id' => $rental->id,
                'payment_type' => 'initial',
                'amount' => $totalCost,
                'payment_proof_path' => $proofPath,
                'payment_date' => now()->toDateString(),
            ]);

            $garage->update([
                'status' => 'used',
            ]);

            return redirect()
                ->route('tenant.rentals.index')
                ->with('success', "Sewa garasi {$garage->code} berhasil diajukan!");
        });
    }
}