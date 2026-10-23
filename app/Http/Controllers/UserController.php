<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Audit\AuditLogger;
use App\Auth\Auth;
use App\Database\Connection;
use App\Support\Csrf;
use App\Support\View;

final class UserController
{
    public function index(): void
    {
        Auth::requireRole(['administrator']);
        $users = Connection::get()->query(
            'SELECT id, username, email, full_name, role, is_active, last_login_at FROM users ORDER BY id'
        )->fetchAll(\PDO::FETCH_ASSOC);
        View::render('users/index', ['user' => Auth::user(), 'users' => $users]);
    }

    public function create(): void
    {
        Auth::requireRole(['administrator']);
        View::render('users/form', ['user' => Auth::user(), 'editUser' => null]);
    }

    public function store(): void
    {
        Auth::requireRole(['administrator']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 10) {
            flash('error', 'Password must be at least 10 characters.');
            redirect('/users/create');
        }
        Connection::get()->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active)
             VALUES (:username, :email, :hash, :name, :role, :active)'
        )->execute([
            'username' => trim((string) $_POST['username']),
            'email' => trim((string) $_POST['email']),
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim((string) $_POST['full_name']),
            'role' => $_POST['role'] ?? 'staff',
            'active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        AuditLogger::log((int) Auth::user()['id'], 'user_created', 'user', (int) Connection::get()->lastInsertId());
        flash('success', 'User created.');
        redirect('/users');
    }

    public function toggle(): void
    {
        Auth::requireRole(['administrator']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) Auth::user()['id']) {
            flash('error', 'You cannot deactivate your own account.');
            redirect('/users');
        }
        Connection::get()->prepare('UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'User status updated.');
        redirect('/users');
    }
}
