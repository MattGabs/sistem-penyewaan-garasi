<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk mengelola data garasi oleh Admin.
 */
class GarageController extends Controller
{
    /**
     * Menampilkan daftar garasi dengan pagination.
     */
    public function index()
    {
        $garages = Garage::orderBy('code', 'asc')->paginate(15);

        return view('admin.garages.index', compact('garages'));
    }

    /**
     * Memvalidasi dan menambahkan garasi baru secara berkelompok.
     *
     * Kode garasi dibuat otomatis berdasarkan tipe garasi,
     * dengan batas maksimal 999 unit untuk setiap tipe.
     * Seluruh proses penyimpanan menggunakan database transaction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:sepeda,mobil,mobil_listrik'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'daily_rate' => ['required', 'integer', 'min:1000'],
            'electricity_rate_per_kwh' => ['required_if:type,mobil_listrik', 'nullable', 'integer', 'min:100'],
        ]);

        $prefixMap = [
            'sepeda' => 'S',
            'mobil' => 'C',
            'mobil_listrik' => 'E',
        ];

        $prefix = $prefixMap[$request->type];
        $lastGarage = Garage::where('type', $request->type)
            ->orderBy('code', 'desc')
            ->first();

        $lastNumber = $lastGarage ? (int) substr($lastGarage->code, 1) : 0;

        if ($lastNumber + $request->quantity > 999) {
            return back()
                ->withErrors(['quantity' => "Total garasi tipe ini maksimal 999 unit (saat ini sudah ada {$lastNumber} unit)."])
                ->withInput();
        }

        DB::transaction(function () use ($request, $prefix, $lastNumber) {
            for ($i = 1; $i <= $request->quantity; $i++) {
                $code = $prefix . str_pad($lastNumber + $i, 3, '0', STR_PAD_LEFT);

                Garage::create([
                    'code' => $code,
                    'type' => $request->type,
                    'daily_rate' => $request->daily_rate,
                    'electricity_rate_per_kwh' => $request->type === 'mobil_listrik'
                        ? $request->electricity_rate_per_kwh
                        : null,
                    'status' => 'available',
                ]);
            }
        });

        return redirect()
            ->route('admin.garages.index')
            ->with('success', "{$request->quantity} unit garasi baru berhasil ditambahkan.");
    }
}