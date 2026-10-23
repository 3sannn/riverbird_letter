<?php

declare(strict_types=1);

namespace App\Clients;

use App\Database\Connection;
use PDO;

final class ClientRepository
{
    public function paginate(int $page = 1, int $perPage = 20, ?string $search = null, bool $archived = false): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = ['archived' => $archived ? 1 : 0];
        $where = 'WHERE is_archived = :archived';
        if ($search) {
            $where .= ' AND (name LIKE :q OR business_name LIKE :q OR contact_person LIKE :q OR email LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $countStmt = Connection::get()->prepare("SELECT COUNT(*) FROM clients {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT * FROM clients {$where} ORDER BY updated_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function find(int $id): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = Connection::get()->prepare(
            'INSERT INTO clients (name, business_name, contact_person, email, phone, address, gstin, notes)
             VALUES (:name, :business_name, :contact_person, :email, :phone, :address, :gstin, :notes)'
        );
        $stmt->execute($data);
        return (int) Connection::get()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Connection::get()->prepare(
            'UPDATE clients SET name = :name, business_name = :business_name, contact_person = :contact_person,
             email = :email, phone = :phone, address = :address, gstin = :gstin, notes = :notes
             WHERE id = :id'
        );
        $data['id'] = $id;
        $stmt->execute($data);
    }

    public function archive(int $id): void
    {
        Connection::get()->prepare('UPDATE clients SET is_archived = 1 WHERE id = :id')->execute(['id' => $id]);
    }

    public function allActive(): array
    {
        return Connection::get()->query(
            'SELECT id, name, business_name FROM clients WHERE is_archived = 0 ORDER BY name'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActive(): int
    {
        return (int) Connection::get()->query('SELECT COUNT(*) FROM clients WHERE is_archived = 0')->fetchColumn();
    }
}
