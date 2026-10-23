<?php

declare(strict_types=1);

namespace App\Templates;

final class TemplateRenderer
{
    public function render(string $layoutView, array $data): string
    {
        $path = base_path('templates/' . str_replace('.', '/', $layoutView) . '.php');
        if (!is_file($path)) {
            throw new \RuntimeException("Template not found: {$layoutView}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}
