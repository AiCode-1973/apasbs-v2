<?php
declare(strict_types=1);

class View
{
    /**
     * @param string $module  pasta em modules/
     * @param string $view    arquivo em modules/<module>/views/
     * @param array  $data    variáveis extraídas para a view
     * @param string|bool $layout  nome do layout, true = 'main', false = sem layout
     */
    public static function render(string $module, string $view, array $data = [], string|bool $layout = true): void
    {
        // Blinda contra qualquer valor inesperado vindo de fora
        $module = preg_replace('/[^a-z0-9_]/', '', strtolower($module)) ?? '';
        $view   = preg_replace('/[^a-z0-9_]/', '', strtolower($view)) ?? '';

        $viewFile = BASE_PATH . "/modules/{$module}/views/{$view}.php";

        if (!is_file($viewFile)) {
            http_response_code(500);
            exit("View não encontrada: modules/{$module}/views/{$view}.php");
        }

        extract($data, EXTR_SKIP);

        if ($layout === false) {
            require $viewFile;
            return;
        }

        $layoutFile = BASE_PATH . '/layouts/' . ($layout === true ? 'main' : $layout) . '.php';

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }
}
