<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@garasi.test'],
            [
                'nik' => '0000000000000000',
                'name' => 'Administrator',
                'phone' => '081234567890',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'verification_status' => 'approved',
            ]
        );
    }
}