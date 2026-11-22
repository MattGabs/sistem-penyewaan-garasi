<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserVerificationController;
use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Tenant\RentalController;

// Route halaman utama aplikasi.
Route::get('/', function () {
    return view('welcome');
});

// Route dashboard yang hanya dapat diakses pengguna yang sudah login dan terverifikasi.
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Mengelompokkan route khusus Admin dengan middleware autentikasi dan pembatasan role.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Menampilkan daftar penyewa dan memproses perubahan status verifikasi.
    Route::get('/verifikasi-penyewa', [UserVerificationController::class, 'index'])->name('users.index');
    Route::patch('/verifikasi-penyewa/{user}', [UserVerificationController::class, 'updateStatus'])->name('users.update');

    // Menampilkan daftar garasi dan memproses penambahan garasi baru.
    Route::get('/garasi', [GarageController::class, 'index'])->name('garages.index');
    Route::post('/garasi', [GarageController::class, 'store'])->name('garages.store');
});

// Mengelompokkan route khusus Penyewa dengan middleware autentikasi dan pembatasan role.
Route::middleware(['auth', 'role:penyewa'])->prefix('penyewa')->name('tenant.')->group(function () {

    // Menampilkan riwayat penyewaan dan formulir penyewaan garasi.
    Route::get('/sewa', [RentalController::class, 'index'])->name('rentals.index');
    Route::get('/sewa/baru', [RentalController::class, 'create'])->name('rentals.create');

    // Memproses pengajuan penyewaan garasi baru.
    Route::post('/sewa', [RentalController::class, 'store'])->name('rentals.store');
});

// Route pengelolaan profil yang hanya dapat diakses pengguna yang sudah login.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Memuat route autentikasi seperti login, logout, dan registrasi.
require __DIR__.'/auth.php';