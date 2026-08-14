<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('publana_view')) {

    /**
     * Render a template.
     */
    function publana_view(string $view, array $data = []): void
    {
        $path = PUBLANA_API_PATH . 'templates/' . $view . '.php';

        if (!file_exists($path)) {
            return;
        }

        extract($data, EXTR_SKIP);

        include $path;
    }

}

if (!function_exists('publana_asset')) {

    /**
     * Get asset URL.
     */
    function publana_asset(string $path): string
    {
        return PUBLANA_API_URL . 'assets/' . ltrim($path, '/');
    }

}

if (!function_exists('publana_url')) {

    /**
     * Plugin URL.
     */
    function publana_url(string $path = ''): string
    {
        return PUBLANA_API_URL . ltrim($path, '/');
    }

}

if (!function_exists('publana_path')) {

    /**
     * Plugin path.
     */
    function publana_path(string $path = ''): string
    {
        return PUBLANA_API_PATH . ltrim($path, '/');
    }

}