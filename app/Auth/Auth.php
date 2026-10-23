<?php

declare(strict_types=1);

namespace App\Auth;

use App\Audit\AuditLogger;
use App\Database\Connection;
use PDO;

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }

        $stmt = Connection::get()->prepare(
            'SELECT id, username, email, full_name, role, is_active FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !(int) $user['is_active']) {
            self::logout();
            return null;
        }

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public static function attempt(string $identifier, string $password): bool
    {
        $throttle = new LoginThrottle();
        if ($throttle->isLockedOut($identifier)) {
            return false;
        }

        $stmt = Connection::get()->prepare(
            'SELECT id, password_hash, is_active FROM users
             WHERE username = :id OR email = :id LIMIT 1'
        );
        $stmt->execute(['id' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $valid = $user
            && (int) $user['is_active'] === 1
            && password_verify($password, $user['password_hash']);

        if (!$valid) {
            $throttle->recordFailure($identifier);
            return false;
        }

        $throttle->clear($identifier);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        Connection::get()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
            ->execute(['id' => $user['id']]);

        AuditLogger::log((int) $user['id'], 'login', 'user', (int) $user['id']);

        return true;
    }

    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            AuditLogger::log((int) $userId, 'logout', 'user', (int) $userId);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function hasUsers(): bool
    {
        return (int) Connection::get()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    }
}
