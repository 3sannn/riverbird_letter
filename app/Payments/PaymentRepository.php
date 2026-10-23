<?php

declare(strict_types=1);

namespace App\Payments;

use App\Database\Connection;
use App\Invoices\InvoiceCalculator;
use PDO;

final class PaymentRepository
{
    public function forDocument(int $documentId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM payments WHERE document_id = :id ORDER BY payment_date DESC, id DESC'
        );
        $stmt->execute(['id' => $documentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(int $userId, int $documentId, array $data): void
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $docStmt = $pdo->prepare('SELECT * FROM documents WHERE id = :id FOR UPDATE');
            $docStmt->execute(['id' => $documentId]);
            $doc = $docStmt->fetch(PDO::FETCH_ASSOC);
            if (!$doc || $doc['document_type'] !== 'invoice') {
                throw new \RuntimeException('Payments apply to invoices only.');
            }
            if (!in_array($doc['status'], ['issued', 'partially_paid', 'paid', 'overdue'], true)) {
                throw new \RuntimeException('Invoice must be issued before recording payments.');
            }

            $amount = decimal($data['amount']);
            if (bccomp($amount, '0', 2) <= 0) {
                throw new \InvalidArgumentException('Payment amount must be positive.');
            }

            $existing = $this->forDocument($documentId);
            $paid = (new InvoiceCalculator())->sumPayments(array_column($existing, 'amount'));
            $newPaid = bcadd($paid, $amount, 2);
            $total = decimal($doc['total_amount']);
            if (bccomp($newPaid, $total, 2) === 1) {
                throw new \InvalidArgumentException('Payment would exceed invoice total.');
            }

            $pdo->prepare(
                'INSERT INTO payments (document_id, payment_date, amount, payment_method, reference, remarks, recorded_by)
                 VALUES (:document_id, :payment_date, :amount, :payment_method, :reference, :remarks, :uid)'
            )->execute([
                'document_id' => $documentId,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'uid' => $userId,
            ]);

            $status = (new InvoiceCalculator())->paymentStatus($total, $newPaid);
            $pdo->prepare(
                'UPDATE documents SET amount_received = :paid, status = :status, updated_by = :uid WHERE id = :id'
            )->execute(['paid' => $newPaid, 'status' => $status, 'uid' => $userId, 'id' => $documentId]);

            $pdo->commit();
            \App\Audit\AuditLogger::log($userId, 'payment_recorded', 'document', $documentId, ['amount' => $amount]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
