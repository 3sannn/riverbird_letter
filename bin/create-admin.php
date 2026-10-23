<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Database\Connection;

if (App\Auth\Auth::hasUsers()) {
    fwrite(STDERR, "An administrator already exists. Use the admin UI to manage users.\n");
    exit(1);
}

$username = $argv[1] ?? null;
$email = $argv[2] ?? null;
$password = $argv[3] ?? null;
$fullName = $argv[4] ?? 'Administrator';

if (!$username || !$email || !$password) {
    fwrite(STDERR, "Usage: php bin/create-admin.php <username> <email> <password> [full name]\n");
    exit(1);
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
Connection::get()->prepare(
    'INSERT INTO users (username, email, password_hash, full_name, role, is_active)
     VALUES (:username, :email, :hash, :name, \'administrator\', 1)'
)->execute([
    'username' => $username,
    'email' => $email,
    'hash' => $hash,
    'name' => $fullName,
]);

echo "Administrator created.\n";
