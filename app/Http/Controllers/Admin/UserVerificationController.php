<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserVerificationController extends Controller
{
    public function index()
    {
        $tenants = User::where('role', 'penyewa')
            ->orderByRaw("CASE WHEN verification_status = 'pending' THEN 1 WHEN verification_status = 'approved' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        return view('admin.users.index', compact('tenants'));
    }

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