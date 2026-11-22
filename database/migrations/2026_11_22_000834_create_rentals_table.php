<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_id')->constrained()->cascadeOnDelete();
            $table->enum('vehicle_type', ['sepeda', 'sepeda_motor', 'mobil', 'mobil_listrik']);
            $table->string('plate_number', 15)->nullable();
            $table->string('vehicle_brand_type', 100);
            $table->decimal('battery_capacity_kwh', 8, 2)->nullable();
            $table->decimal('remaining_battery_kwh', 8, 2)->nullable();
            $table->enum('rental_type', ['harian', 'bulanan']);
            $table->unsignedInteger('duration_days');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('electricity_cost')->default(0);
            $table->unsignedInteger('total_cost');
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};