<?php

namespace Tests\Unit;

use App\Models\Payment;
use PHPUnit\Framework\TestCase;

class PaymentUnitTest extends TestCase
{
    public function test_it_validates_payment_amount_must_be_greater_than_zero(): void
    {
        $this->assertTrue(Payment::isValidAmount(50000));
        $this->assertFalse(Payment::isValidAmount(0));
        $this->assertFalse(Payment::isValidAmount(-15000));
    }

    public function test_it_validates_allowed_payment_proof_extensions(): void
    {
        $this->assertTrue(Payment::isAllowedProofExtension('transfer_bca.jpg'));
        $this->assertTrue(Payment::isAllowedProofExtension('BUKTI_BAYAR.PNG'));
        $this->assertTrue(Payment::isAllowedProofExtension('struk_sewa.pdf'));

        $this->assertFalse(Payment::isAllowedProofExtension('virus.exe'));
        $this->assertFalse(Payment::isAllowedProofExtension('dokumen.docx'));
    }

    public function test_it_calculates_total_revenue_from_initial_and_extension_payments(): void
    {
        $payments = [
            new Payment(['payment_type' => 'initial', 'amount' => 1290000]),
            new Payment(['payment_type' => 'extension', 'amount' => 250000]),
        ];

        $this->assertEquals(1540000, Payment::calculateTotalRevenue($payments));
        $this->assertEquals(0, Payment::calculateTotalRevenue([]));
    }
}