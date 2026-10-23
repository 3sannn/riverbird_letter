<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Connection;
use App\Invoices\InvoiceNumberService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InvoiceNumberAllocationTest extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('RUN_DB_TESTS')) {
            $this->markTestSkipped('Set RUN_DB_TESTS=1 and configure .env to run allocation tests.');
        }
        require dirname(__DIR__) . '/bootstrap.php';
    }

    public function testAllocateIncrementsWithinMonth(): void
    {
        $pdo = Connection::get();
        $pdo->exec("DELETE FROM invoice_sequences WHERE period_key = '1026'");
        $svc = new InvoiceNumberService();
        $date = new DateTimeImmutable('2026-10-05');
        $n1 = $svc->allocateForDate($date);
        $n2 = $svc->allocateForDate($date);
        $this->assertSame('RB-102601', $n1);
        $this->assertSame('RB-102602', $n2);
    }
}
