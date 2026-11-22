<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Controller untuk menangani proses registrasi pengguna baru.
 */
class RegisteredUserController extends Controller
{
    /**
     * Menampilkan halaman formulir pendaftaran pengguna.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Memvalidasi dan menyimpan data pendaftaran pengguna baru.
     *
     * Akun yang berhasil didaftarkan secara otomatis mendapatkan
     * role penyewa dan status verifikasi pending.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
       $request->validate([
            'nik' => ['required', 'string', 'size:16', 'unique:'.User::class],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'nik' => $request->nik,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}