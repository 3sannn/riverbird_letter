<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Auth;
use App\Support\Csrf;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (!Auth::hasUsers()) {
            redirect('/setup');
        }
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/login', [], 'layouts/auth');
    }

    public function login(): void
    {
        Csrf::validate($_POST['_csrf'] ?? null);
        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        remember_old(['identifier' => $identifier]);

        if (Auth::attempt($identifier, $password)) {
            clear_old();
            redirect('/');
        }

        flash('error', 'Invalid credentials or account locked. Please try again later.');
        redirect('/login');
    }

    public function logout(): void
    {
        Csrf::validate($_POST['_csrf'] ?? null);
        Auth::logout();
        redirect('/login');
    }
}
