<?php

declare(strict_types=1);

namespace App\Documents;

use App\Audit\AuditLogger;
use App\Database\Connection;
use App\Invoices\InvoiceCalculator;
use App\Invoices\InvoiceNumberService;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class DocumentService
{
    public function __construct(
        private DocumentRepository $documents = new DocumentRepository(),
        private InvoiceCalculator $calculator = new InvoiceCalculator(),
        private InvoiceNumberService $numberService = new InvoiceNumberService(),
    ) {
    }

    public function saveDraft(int $userId, array $payload, ?int $documentId = null): int
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $items = $payload['items'] ?? [];
            $calc = $this->calculator->calculate(
                $items,
                (string) ($payload['discount_amount'] ?? '0'),
                (string) ($payload['tax_rate'] ?? '0'),
                (string) ($payload['shipping_amount'] ?? '0')
            );

            $data = [
                'client_id' => $payload['client_id'] ?: null,
                'template_id' => $payload['template_id'] ?: null,
                'document_type' => $payload['document_type'],
                'status' => 'draft',
                'title' => $payload['title'],
                'document_date' => $payload['document_date'] ?: null,
                'due_date' => $payload['due_date'] ?: null,
                'subtotal' => $calc['subtotal'],
                'discount_amount' => decimal($payload['discount_amount'] ?? 0),
                'tax_rate' => decimal($payload['tax_rate'] ?? 0),
                'tax_amount' => $calc['tax_amount'],
                'shipping_amount' => decimal($payload['shipping_amount'] ?? 0),
                'total_amount' => $calc['total'],
                'remarks' => $payload['remarks'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'terms' => $payload['terms'] ?? null,
                'content_json' => json_encode($payload['content'] ?? [], JSON_THROW_ON_ERROR),
                'updated_by' => $userId,
            ];

            if ($documentId) {
                $existing = $this->documents->find($documentId);
                if (!$existing || $existing['status'] !== 'draft') {
                    throw new RuntimeException('Only draft documents can be edited this way.');
                }
                $sql = 'UPDATE documents SET client_id=:client_id, template_id=:template_id, document_type=:document_type,
                        title=:title, document_date=:document_date, due_date=:due_date, subtotal=:subtotal,
                        discount_amount=:discount_amount, tax_rate=:tax_rate, tax_amount=:tax_amount,
                        shipping_amount=:shipping_amount, total_amount=:total_amount, remarks=:remarks,
                        notes=:notes, terms=:terms, content_json=:content_json, updated_by=:updated_by
                        WHERE id=:id AND status=\'draft\'';
                $data['id'] = $documentId;
                $pdo->prepare($sql)->execute($data);
                $this->replaceItems($pdo, $documentId, $items, $calc['items']);
                $this->saveVersion($pdo, $documentId, $userId);
                $id = $documentId;
            } else {
                $sql = 'INSERT INTO documents (client_id, template_id, document_type, status, title, document_date, due_date,
                        subtotal, discount_amount, tax_rate, tax_amount, shipping_amount, total_amount,
                        remarks, notes, terms, content_json, created_by, updated_by)
                        VALUES (:client_id, :template_id, :document_type, \'draft\', :title, :document_date, :due_date,
                        :subtotal, :discount_amount, :tax_rate, :tax_amount, :shipping_amount, :total_amount,
                        :remarks, :notes, :terms, :content_json, :created_by, :updated_by)';
                $data['created_by'] = $userId;
                $pdo->prepare($sql)->execute($data);
                $id = (int) $pdo->lastInsertId();
                $this->replaceItems($pdo, $id, $items, $calc['items']);
            }

            $pdo->commit();
            AuditLogger::log($userId, 'document_save_draft', 'document', $id);
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function issueInvoice(int $userId, int $documentId): void
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM documents WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $documentId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$doc || $doc['document_type'] !== 'invoice') {
                throw new RuntimeException('Invalid invoice document.');
            }
            if ($doc['status'] !== 'draft') {
                // Idempotent: already issued
                $pdo->commit();
                return;
            }
            if (empty($doc['document_date'])) {
                throw new RuntimeException('Invoice date is required before issuing.');
            }

            $invoiceDate = new DateTimeImmutable($doc['document_date']);
            $number = $this->numberService->allocateForDate($invoiceDate);

            $items = $this->documents->getItems($documentId);
            $client = null;
            if ($doc['client_id']) {
                $client = (new \App\Clients\ClientRepository())->find((int) $doc['client_id']);
            }

            $snapshot = [
                'document' => $doc,
                'items' => $items,
                'client' => $client,
                'company' => config('company'),
                'issued_number' => $number,
                'issued_at' => date('c'),
            ];

            $update = $pdo->prepare(
                "UPDATE documents SET status = 'issued', document_number = :num, issued_at = NOW(),
                 issued_snapshot = :snapshot, updated_by = :uid WHERE id = :id AND status = 'draft'"
            );
            $update->execute([
                'num' => $number,
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'uid' => $userId,
                'id' => $documentId,
            ]);

            $this->saveVersion($pdo, $documentId, $userId, true);
            $pdo->commit();
            AuditLogger::log($userId, 'invoice_issued', 'document', $documentId, ['number' => $number]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function duplicate(int $userId, int $documentId): int
    {
        $doc = $this->documents->find($documentId);
        if (!$doc) {
            throw new RuntimeException('Document not found.');
        }
        $items = $this->documents->getItems($documentId);
        $content = json_decode($doc['content_json'] ?? '{}', true) ?: [];

        $payload = [
            'client_id' => $doc['client_id'],
            'template_id' => $doc['template_id'],
            'document_type' => $doc['document_type'],
            'title' => $doc['title'] . ' (Copy)',
            'document_date' => $doc['document_date'],
            'due_date' => $doc['due_date'],
            'discount_amount' => $doc['discount_amount'],
            'tax_rate' => $doc['tax_rate'],
            'shipping_amount' => $doc['shipping_amount'],
            'remarks' => $doc['remarks'],
            'notes' => $doc['notes'],
            'terms' => $doc['terms'],
            'content' => $content,
            'items' => array_map(fn ($i) => [
                'description' => $i['description'],
                'quantity' => $i['quantity'],
                'unit_price' => $i['unit_price'],
            ], $items),
        ];

        $newId = $this->saveDraft($userId, $payload);
        AuditLogger::log($userId, 'document_duplicated', 'document', $newId, ['source' => $documentId]);
        return $newId;
    }

    public function importHistoricalInvoice(int $userId, array $data): int
    {
        $pdo = Connection::get();
        $pdo->beginTransaction();
        try {
            $items = $data['items'];
            $calc = $this->calculator->calculate($items, '0', '0', '0');
            $stmt = $pdo->prepare(
                "INSERT INTO documents (client_id, document_type, status, title, document_number, document_date,
                 subtotal, total_amount, amount_received, remarks, issued_at, issued_snapshot, created_by, updated_by)
                 VALUES (:client_id, 'invoice', :status, :title, :document_number, :document_date,
                 :subtotal, :total, :received, :remarks, :issued_at, :snapshot, :uid, :uid)"
            );
            $snapshot = $data['snapshot'];
            $stmt->execute([
                'client_id' => $data['client_id'],
                'status' => $data['status'],
                'title' => $data['title'],
                'document_number' => $data['document_number'],
                'document_date' => $data['document_date'],
                'subtotal' => $calc['subtotal'],
                'total' => $calc['total'],
                'received' => $data['amount_received'],
                'remarks' => $data['remarks'],
                'issued_at' => $data['issued_at'],
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'uid' => $userId,
            ]);
            $id = (int) $pdo->lastInsertId();
            $this->replaceItems($pdo, $id, $items, $calc['items']);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function replaceItems(PDO $pdo, int $documentId, array $items, array $computed): void
    {
        $pdo->prepare('DELETE FROM document_items WHERE document_id = :id')->execute(['id' => $documentId]);
        $insert = $pdo->prepare(
            'INSERT INTO document_items (document_id, sort_order, description, quantity, unit_price, line_total)
             VALUES (:document_id, :sort_order, :description, :quantity, :unit_price, :line_total)'
        );
        foreach ($items as $index => $item) {
            if (trim((string) ($item['description'] ?? '')) === '') {
                continue;
            }
            $insert->execute([
                'document_id' => $documentId,
                'sort_order' => $index,
                'description' => $item['description'],
                'quantity' => decimal($item['quantity'] ?? 1),
                'unit_price' => decimal($item['unit_price'] ?? 0),
                'line_total' => $computed[$index]['line_total'] ?? '0.00',
            ]);
        }
    }

    private function saveVersion(PDO $pdo, int $documentId, int $userId, bool $issued = false): void
    {
        $doc = $this->documents->find($documentId);
        $items = $this->documents->getItems($documentId);
        $ver = (int) $pdo->query(
            "SELECT COALESCE(MAX(version_number), 0) + 1 FROM document_versions WHERE document_id = {$documentId}"
        )->fetchColumn();
        $snapshot = ['document' => $doc, 'items' => $items, 'issued' => $issued];
        $pdo->prepare(
            'INSERT INTO document_versions (document_id, version_number, snapshot_json, created_by)
             VALUES (:doc, :ver, :snap, :uid)'
        )->execute([
            'doc' => $documentId,
            'ver' => $ver,
            'snap' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'uid' => $userId,
        ]);
    }
}
