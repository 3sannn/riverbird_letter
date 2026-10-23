<?php

declare(strict_types=1);

namespace App\Documents;

use App\Database\Connection;
use PDO;

final class DocumentRepository
{
    public function find(int $id): ?array
    {
        $stmt = Connection::get()->prepare(
            'SELECT d.*, c.name AS client_name, c.business_name, c.contact_person, c.address AS client_address
             FROM documents d
             LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getItems(int $documentId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM document_items WHERE document_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $documentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginate(array $filters, int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = ['d.is_archived = 0'];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'd.document_type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'd.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['client_id'])) {
            $where[] = 'd.client_id = :client_id';
            $params['client_id'] = (int) $filters['client_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(d.title LIKE :q OR d.document_number LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $countStmt = Connection::get()->prepare("SELECT COUNT(*) FROM documents d {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT d.*, c.name AS client_name FROM documents d
                LEFT JOIN clients c ON c.id = d.client_id
                {$whereSql}
                ORDER BY d.updated_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
    }

    public function dashboardStats(): array
    {
        $pdo = Connection::get();
        return [
            'total_documents' => (int) $pdo->query('SELECT COUNT(*) FROM documents WHERE is_archived = 0')->fetchColumn(),
            'drafts' => (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'draft' AND is_archived = 0")->fetchColumn(),
            'issued_invoices' => (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE document_type = 'invoice' AND status IN ('issued','paid','partially_paid','overdue')")->fetchColumn(),
            'paid_invoices' => (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE document_type = 'invoice' AND status = 'paid'")->fetchColumn(),
            'unpaid_invoices' => (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE document_type = 'invoice' AND status IN ('issued','partially_paid','overdue')")->fetchColumn(),
        ];
    }

    public function recentDocuments(int $limit = 8): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT d.id, d.title, d.document_type, d.status, d.document_number, d.updated_at, c.name AS client_name
             FROM documents d LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.is_archived = 0 ORDER BY d.updated_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recentActivity(int $limit = 10): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT a.*, u.full_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
