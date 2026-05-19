<?php
/**
 * View Rendering Helper
 */

if (!function_exists('view')) {
    function view(string $template, array $data = [], bool $return = false): ?string {
        $path = RESOURCE_PATH . DS . 'views' . DS . str_replace('.', DS, $template) . '.php';
        if (!file_exists($path)) {
            throw new \RuntimeException("View not found: {$template} at {$path}");
        }
        extract($data, EXTR_SKIP);
        if ($return) {
            ob_start();
            include $path;
            return ob_get_clean();
        }
        include $path;
        return null;
    }
}

if (!function_exists('partial')) {
    function partial(string $template, array $data = []): void {
        view($template, $data);
    }
}
