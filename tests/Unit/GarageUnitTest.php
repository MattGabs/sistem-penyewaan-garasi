<?php

namespace Tests\Unit;

use App\Models\Garage;
use PHPUnit\Framework\TestCase;

class GarageUnitTest extends TestCase
{
    public function test_it_formats_garage_code_correctly_by_type(): void
    {
        $this->assertEquals('S001', Garage::formatCode('sepeda', 1));
        $this->assertEquals('C025', Garage::formatCode('mobil', 25));
        $this->assertEquals('E999', Garage::formatCode('mobil_listrik', 999));
    }

    public function test_it_throws_exception_for_invalid_garage_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Garage::formatCode('pesawat', 1);
    }

    public function test_it_calculates_monthly_rate_as_25_times_daily_rate(): void
    {
        $garage = new Garage([
            'type' => 'mobil',
            'daily_rate' => 40000,
        ]);

        $this->assertEquals(1000000, $garage->calculateMonthlyRate());
    }

    public function test_it_calculates_electricity_cost_for_electric_car_garage(): void
    {
        $evGarage = new Garage([
            'type' => 'mobil_listrik',
            'daily_rate' => 50000,
            'electricity_rate_per_kwh' => 2000,
        ]);

        // Kapasitas 26.7 kWh - sisa 6.7 kWh = 20 kWh * Rp 2.000 = Rp 40.000
        $this->assertEquals(40000, $evGarage->calculateElectricityCost(26.7, 6.7));

        // Jika sisa baterai sama dengan kapasitas maksimal, biaya listrik harus 0
        $this->assertEquals(0, $evGarage->calculateElectricityCost(26.7, 26.7));
    }

    public function test_it_returns_zero_electricity_cost_for_non_electric_garage(): void
    {
        $regularGarage = new Garage([
            'type' => 'mobil',
            'daily_rate' => 40000,
            'electricity_rate_per_kwh' => null,
        ]);

        $this->assertEquals(0, $regularGarage->calculateElectricityCost(50, 10));
    }
}