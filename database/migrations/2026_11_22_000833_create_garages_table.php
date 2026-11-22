<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel garages untuk menyimpan data garasi,
     * tipe garasi, tarif sewa, tarif listrik, dan status ketersediaan.
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->enum('type', ['sepeda', 'mobil', 'mobil_listrik']);
            $table->unsignedInteger('daily_rate');
            $table->unsignedInteger('electricity_rate_per_kwh')->nullable();
            $table->enum('status', ['available', 'used'])->default('available');
            $table->timestamps();
        });
    }

     /**
     * Menghapus tabel garages saat migration di-rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};