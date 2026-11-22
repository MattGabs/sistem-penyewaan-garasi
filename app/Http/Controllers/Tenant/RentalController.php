<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk mengelola proses penyewaan garasi oleh Penyewa.
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
     * Menampilkan formulir penyewaan beserta daftar garasi yang tersedia.
     *
     * Hanya penyewa dengan status verifikasi approved yang dapat
     * mengakses halaman penyewaan.
     */
    public function create(Request $request)
    {
        if ($request->user()->verification_status !== 'approved') {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Akun Anda belum terverifikasi oleh Admin sehingga belum dapat menyewa garasi.');
        }

        $availableGarages = Garage::where('status', 'available')
            ->orderBy('code')
            ->get(['id', 'code', 'type', 'daily_rate', 'electricity_rate_per_kwh']);

        return view('tenant.rentals.create', compact('availableGarages'));
    }

    /**
     * Memvalidasi dan memproses pengajuan penyewaan garasi.
     *
     * Memeriksa kesesuaian tipe kendaraan dan garasi, menghitung
     * biaya penyewaan serta listrik, menyimpan transaksi dan bukti
     * pembayaran, kemudian memperbarui status garasi menjadi used.
     */
    public function store(Request $request)
    {
        if ($request->user()->verification_status !== 'approved') {
            abort(403, 'Akun Anda belum diverifikasi.');
        }

        $request->validate([
            'vehicle_type' => ['required', 'in:sepeda,sepeda_motor,mobil,mobil_listrik'],
            'plate_number' => ['nullable', 'string', 'max:20', 'required_unless:vehicle_type,sepeda'],
            'vehicle_brand_type' => ['required', 'string', 'max:100'],
            'battery_capacity_kwh' => ['nullable', 'numeric', 'min:1', 'max:500', 'required_if:vehicle_type,mobil_listrik'],
            'remaining_battery_kwh' => ['nullable', 'numeric', 'min:0', 'lte:battery_capacity_kwh'],
            'garage_type' => ['required', 'in:sepeda,mobil,mobil_listrik'],
            'garage_id' => ['required', 'exists:garages,id'],
            'rental_type' => ['required', 'in:harian,bulanan'],
            'duration' => ['required', 'integer', 'min:1', 'max:365'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        // Memastikan tipe garasi sesuai dengan jenis kendaraan yang disewakan.
        $allowedGarageTypes = match ($request->vehicle_type) {
            'sepeda', 'sepeda_motor' => ['sepeda'],
            'mobil' => ['mobil'],
            'mobil_listrik' => ['mobil', 'mobil_listrik'],
        };

        if (! in_array($request->garage_type, $allowedGarageTypes, true)) {
            return back()
                ->withInput()
                ->withErrors(['garage_type' => 'Tipe garasi tidak sesuai dengan jenis kendaraan yang dipilih.']);
        }

        if ($request->garage_type === 'mobil_listrik' && ($request->remaining_battery_kwh === null || $request->remaining_battery_kwh === '')) {
            return back()
                ->withInput()
                ->withErrors(['remaining_battery_kwh' => 'Sisa baterai saat ini wajib diisi jika memilih Garasi Mobil Listrik.']);
        }

        return DB::transaction(function () use ($request) {
            // Memastikan unit yang dipilih masih tersedia dan mengunci data selama transaksi.
            $garage = Garage::where('id', $request->garage_id)
                ->where('type', $request->garage_type)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (! $garage) {
                return back()
                    ->withInput()
                    ->withErrors(['garage_id' => 'Unit garasi yang dipilih sudah terpakai atau tidak sesuai tipe. Silakan pilih unit lain.']);
            }

            $duration = (int) $request->duration;
            $schedule = Rental::calculateSchedule($request->start_date, $request->rental_type, $duration);

            // Biaya listrik hanya dihitung untuk garasi mobil listrik.
            $electricityCost = 0;
            if ($garage->type === 'mobil_listrik') {
                $electricityCost = $garage->calculateElectricityCost(
                    (float) $request->battery_capacity_kwh,
                    (float) ($request->remaining_battery_kwh ?? 0)
                );
            }

            $totalCost = Rental::calculateTotalCost(
                $garage->daily_rate,
                $request->rental_type,
                $duration,
                $electricityCost
            );

            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            $rental = Rental::create([
                'user_id' => $request->user()->id,
                'garage_id' => $garage->id,
                'vehicle_type' => $request->vehicle_type,
                'plate_number' => $request->vehicle_type === 'sepeda' ? null : strtoupper($request->plate_number),
                'vehicle_brand_type' => $request->vehicle_brand_type,
                'battery_capacity_kwh' => $request->vehicle_type === 'mobil_listrik' ? $request->battery_capacity_kwh : null,
                'remaining_battery_kwh' => $garage->type === 'mobil_listrik' ? $request->remaining_battery_kwh : null,
                'rental_type' => $request->rental_type,
                'duration_days' => $schedule['duration_days'],
                'start_date' => $request->start_date,
                'end_date' => $schedule['end_date'],
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

            $garage->update(['status' => 'used']);

            return redirect()
                ->route('tenant.rentals.index')
                ->with('success', "Berhasil menyewa garasi {$garage->code}. Status garasi kini terpakai (used).");
        });
    }
}