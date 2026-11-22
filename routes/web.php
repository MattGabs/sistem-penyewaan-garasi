<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserVerificationController;
use App\Http\Controllers\Admin\GarageController;

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

// Route pengelolaan profil yang hanya dapat diakses pengguna yang sudah login.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Memuat route autentikasi seperti login, logout, dan registrasi.
require __DIR__.'/auth.php';