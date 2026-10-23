<?php

declare(strict_types=1);

namespace Tests;

use App\Invoices\InvoiceCalculator;
use PHPUnit\Framework\TestCase;

final class InvoiceCalculatorTest extends TestCase
{
    public function testLineTotalsAndTax(): void
    {
        $calc = new InvoiceCalculator();
        $result = $calc->calculate(
            [
                ['quantity' => '1', 'unit_price' => '18000'],
            ],
            '0',
            '0',
            '0'
        );
        $this->assertSame('18000.00', $result['subtotal']);
        $this->assertSame('18000.00', $result['total']);
    }

    public function testPaymentStatus(): void
    {
        $calc = new InvoiceCalculator();
        $this->assertSame('paid', $calc->paymentStatus('7500.00', '7500.00'));
        $this->assertSame('partially_paid', $calc->paymentStatus('10000.00', '5000.00'));
        $this->assertSame('issued', $calc->paymentStatus('10000.00', '0.00'));
    }
}
