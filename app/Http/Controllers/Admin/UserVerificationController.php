<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola verifikasi pendaftaran penyewa oleh Admin.
 */
class UserVerificationController extends Controller
{
    /**
     * Menampilkan daftar penyewa berdasarkan urutan status verifikasi.
     *
     * Penyewa dengan status pending ditampilkan terlebih dahulu,
     * kemudian approved dan rejected.
     */
    public function index()
    {
        $tenants = User::where('role', 'penyewa')
            ->orderByRaw("CASE WHEN verification_status = 'pending' THEN 1 WHEN verification_status = 'approved' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        return view('admin.users.index', compact('tenants'));
    }

    /**
     * Memperbarui status verifikasi pendaftaran penyewa.
     */
    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'verification_status' => ['required', 'in:approved,rejected'],
        ]);

        $user->update([
            'verification_status' => $request->verification_status,
        ]);

        return back()->with('success', "Status verifikasi {$user->name} berhasil diperbarui.");
    }
}