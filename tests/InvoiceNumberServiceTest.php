<?php

declare(strict_types=1);

namespace Tests;

use App\Invoices\InvoiceNumberService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InvoiceNumberServiceTest extends TestCase
{
    public function testFormatNumber(): void
    {
        $svc = new InvoiceNumberService();
        $date = new DateTimeImmutable('2026-09-15');
        $this->assertSame('RB-092601', $svc->formatNumber($date, 1));
        $this->assertSame('RB-092602', $svc->formatNumber($date, 2));
        $date2 = new DateTimeImmutable('2027-01-03');
        $this->assertSame('RB-012701', $svc->formatNumber($date2, 1));
    }

    public function testValidateFormat(): void
    {
        $svc = new InvoiceNumberService();
        $this->assertTrue($svc->validateFormat('RB-092602'));
        $this->assertFalse($svc->validateFormat('RB2026010'));
    }

    public function testRejectsInvalidSequence(): void
    {
        $svc = new InvoiceNumberService();
        $this->expectException(\RuntimeException::class);
        $svc->formatNumber(new DateTimeImmutable('2026-10-01'), 0);
    }
}
