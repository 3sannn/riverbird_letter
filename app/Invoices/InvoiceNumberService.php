<?php

declare(strict_types=1);

namespace App\Invoices;

use App\Database\Connection;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class InvoiceNumberService
{
    public const MAX_SEQUENCE = 99;

    public function allocateForDate(DateTimeImmutable $invoiceDate): string
    {
        $periodKey = $invoiceDate->format('my'); // MMYY
        $pdo = Connection::get();

        $pdo->beginTransaction();
        try {
            $select = $pdo->prepare(
                'SELECT id, last_sequence FROM invoice_sequences WHERE period_key = :period FOR UPDATE'
            );
            $select->execute(['period' => $periodKey]);
            $row = $select->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $insert = $pdo->prepare(
                    'INSERT INTO invoice_sequences (period_key, last_sequence) VALUES (:period, 0)'
                );
                $insert->execute(['period' => $periodKey]);
                $lastSequence = 0;
            } else {
                $lastSequence = (int) $row['last_sequence'];
            }

            $next = $lastSequence + 1;
            if ($next > self::MAX_SEQUENCE) {
                throw new RuntimeException(
                    'Invoice sequence limit reached for period ' . $periodKey . '. Contact administrator.'
                );
            }

            $update = $pdo->prepare(
                'UPDATE invoice_sequences SET last_sequence = :seq WHERE period_key = :period'
            );
            $update->execute(['seq' => $next, 'period' => $periodKey]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return $this->formatNumber($invoiceDate, $next);
    }

    public function formatNumber(DateTimeImmutable $invoiceDate, int $sequence): string
    {
        if ($sequence < 1 || $sequence > self::MAX_SEQUENCE) {
            throw new RuntimeException('Invalid invoice sequence.');
        }

        return sprintf(
            'RB-%s%02d',
            $invoiceDate->format('my'),
            $sequence
        );
    }

    public function validateFormat(string $number): bool
    {
        return (bool) preg_match('/^RB-\d{6}$/', $number);
    }
}
