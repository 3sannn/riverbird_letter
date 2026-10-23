<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\Connection;

final class LoginThrottle
{
    public function isLockedOut(string $identifier): bool
    {
        $max = config('app.login_max_attempts');
        $minutes = config('app.login_lockout_minutes');
        $stmt = Connection::get()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = :id AND attempted_at > (NOW() - INTERVAL :mins MINUTE)'
        );
        $stmt->bindValue(':id', $identifier);
        $stmt->bindValue(':mins', $minutes, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() >= $max;
    }

    public function recordFailure(string $identifier): void
    {
        $stmt = Connection::get()->prepare(
            'INSERT INTO login_attempts (identifier, ip_address) VALUES (:id, :ip)'
        );
        $stmt->execute([
            'id' => $identifier,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }

    public function clear(string $identifier): void
    {
        $stmt = Connection::get()->prepare('DELETE FROM login_attempts WHERE identifier = :id');
        $stmt->execute(['id' => $identifier]);
    }
}
