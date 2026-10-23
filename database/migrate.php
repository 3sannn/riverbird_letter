<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Database\Connection;

$pdo = Connection::get();
$sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Could not read schema.sql\n");
    exit(1);
}

$pdo->exec($sql);
echo "Migration completed.\n";
