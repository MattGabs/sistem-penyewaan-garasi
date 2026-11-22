<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     /**
     * Membuat tabel payments untuk menyimpan riwayat pembayaran,
     * termasuk jenis pembayaran, nominal, bukti pembayaran,
     * dan tanggal pembayaran.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Riwayat pembayaran ikut terhapus jika rental dihapus.
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();

            $table->enum('payment_type', ['initial', 'extension']);
            $table->unsignedInteger('amount');
            $table->string('payment_proof_path');
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel payments saat migration di-rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};