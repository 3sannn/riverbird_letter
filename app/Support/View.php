<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = base_path('views/' . str_replace('.', '/', $view) . '.php');
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = base_path('views/' . str_replace('.', '/', $layout) . '.php');
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    public static function partial(string $partial, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require base_path('views/partials/' . $partial . '.php');
    }
}
