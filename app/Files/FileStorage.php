<?php

declare(strict_types=1);

namespace App\Files;

use App\Database\Connection;
use RuntimeException;

final class FileStorage
{
    public function storeUpload(int $userId, ?int $documentId, array $file): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed.');
        }
        $max = config('app.upload_max_bytes');
        if (($file['size'] ?? 0) > $max) {
            throw new RuntimeException('File exceeds maximum allowed size.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        $allowed = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
        ];
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('File type not allowed.');
        }

        $storageName = bin2hex(random_bytes(16)) . '.' . $this->extensionForMime($mime);
        $dir = storage_path('uploads');
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $dest = $dir . DIRECTORY_SEPARATOR . $storageName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not store uploaded file.');
        }

        Connection::get()->prepare(
            'INSERT INTO file_assets (document_id, storage_name, original_name, mime_type, size_bytes, category, uploaded_by)
             VALUES (:doc, :storage, :original, :mime, :size, \'upload\', :uid)'
        )->execute([
            'doc' => $documentId,
            'storage' => $storageName,
            'original' => basename($file['name']),
            'mime' => $mime,
            'size' => (int) $file['size'],
            'uid' => $userId,
        ]);

        return (int) Connection::get()->lastInsertId();
    }

    public function storeGeneratedPdf(int $userId, int $documentId, string $binary, string $filename): int
    {
        $dir = storage_path('documents');
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $storageName = bin2hex(random_bytes(16)) . '.pdf';
        file_put_contents($dir . DIRECTORY_SEPARATOR . $storageName, $binary);

        Connection::get()->prepare(
            'INSERT INTO file_assets (document_id, storage_name, original_name, mime_type, size_bytes, category, uploaded_by)
             VALUES (:doc, :storage, :original, :mime, :size, \'generated_pdf\', :uid)'
        )->execute([
            'doc' => $documentId,
            'storage' => $storageName,
            'original' => $filename,
            'mime' => 'application/pdf',
            'size' => strlen($binary),
            'uid' => $userId,
        ]);

        return (int) Connection::get()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM file_assets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function resolvePath(array $asset): string
    {
        $base = $asset['category'] === 'generated_pdf'
            ? storage_path('documents')
            : storage_path('uploads');
        $path = $base . DIRECTORY_SEPARATOR . $asset['storage_name'];
        $realBase = realpath($base);
        $realPath = realpath($path);
        if ($realBase === false || $realPath === false || !str_starts_with($realPath, $realBase)) {
            throw new RuntimeException('Invalid file path.');
        }
        return $realPath;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            default => 'bin',
        };
    }
}
