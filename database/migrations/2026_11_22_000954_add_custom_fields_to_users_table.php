<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     /**
     * Menambahkan kolom NIK, nomor telepon, role,
     * dan status verifikasi pada tabel users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->unique()->after('id');
            $table->string('phone', 20)->after('name');
            $table->enum('role', ['admin', 'penyewa'])->default('penyewa')->after('password');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('role');
        });
    }

    /**
    * Menghapus seluruh kolom tambahan saat migration di-rollback.
    */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'phone', 'role', 'verification_status']);
        });
    }
};