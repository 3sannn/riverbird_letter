<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Auth;
use App\Database\Connection;
use App\Support\Csrf;
use App\Support\View;

final class SetupController
{
    public function show(): void
    {
        if (Auth::hasUsers()) {
            redirect('/login');
        }
        View::render('setup/index', [], 'layouts/auth');
    }

    public function store(): void
    {
        if (Auth::hasUsers()) {
            redirect('/login');
        }
        Csrf::validate($_POST['_csrf'] ?? null);

        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $fullName = trim((string) ($_POST['full_name'] ?? 'Administrator'));

        if ($username === '' || $email === '' || strlen($password) < 10) {
            flash('error', 'Please provide username, email, and a password of at least 10 characters.');
            redirect('/setup');
        }

        Connection::get()->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active)
             VALUES (:username, :email, :hash, :name, \'administrator\', 1)'
        )->execute([
            'username' => $username,
            'email' => $email,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $fullName,
        ]);

        flash('success', 'Administrator account created. Please sign in.');
        redirect('/login');
    }
}
