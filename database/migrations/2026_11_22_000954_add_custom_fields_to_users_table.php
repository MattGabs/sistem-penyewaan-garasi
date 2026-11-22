<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->unique()->after('id');
            $table->string('phone', 20)->after('name');
            $table->enum('role', ['admin', 'penyewa'])->default('penyewa')->after('password');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'phone', 'role', 'verification_status']);
        });
    }
};