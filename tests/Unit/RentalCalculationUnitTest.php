<?php

namespace Tests\Unit;

use App\Models\Rental;
use PHPUnit\Framework\TestCase;

class RentalCalculationUnitTest extends TestCase
{
    public function test_it_calculates_daily_rental_schedule(): void
    {
        $schedule = Rental::calculateSchedule('2027-02-10', 'harian', 5);

        $this->assertEquals('2027-02-15', $schedule['end_date']);
        $this->assertEquals(5, $schedule['duration_days']);
    }

    public function test_it_calculates_monthly_rental_schedule_normally(): void
    {
        // 16 Januari -> 16 Februari = 31 hari
        $schedule = Rental::calculateSchedule('2027-01-16', 'bulanan', 1);

        $this->assertEquals('2027-02-16', $schedule['end_date']);
        $this->assertEquals(31, $schedule['duration_days']);
    }

    public function test_it_handles_end_of_month_overflow_for_january_31(): void
    {
        // 31 Januari + 1 bulan tanpa overflow -> 28 Februari (28 hari)
        $schedule = Rental::calculateSchedule('2027-01-31', 'bulanan', 1);

        $this->assertEquals('2027-02-28', $schedule['end_date']);
        $this->assertEquals(28, $schedule['duration_days']);
    }

    public function test_it_calculates_total_rental_cost_for_daily_and_monthly_packages(): void
    {
        // Paket harian: 3 hari * Rp 20.000 = Rp 60.000
        $dailyTotal = Rental::calculateTotalCost(20000, 'harian', 3, 0);
        $this->assertEquals(60000, $dailyTotal);

        // Paket bulanan + listrik EV: (2 bulan * 25 * Rp 50.000) + Rp 40.000 = Rp 2.540.000
        $monthlyEvTotal = Rental::calculateTotalCost(50000, 'bulanan', 2, 40000);
        $this->assertEquals(2540000, $monthlyEvTotal);
    }
}