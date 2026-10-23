<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Auth;
use App\Files\FileStorage;

final class FileController
{
    public function download(): void
    {
        Auth::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $storage = new FileStorage();
        $asset = $storage->find($id);
        if (!$asset) {
            http_response_code(404);
            exit;
        }

        $path = $storage->resolvePath($asset);
        header('Content-Type: ' . $asset['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($asset['original_name']) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }
}
