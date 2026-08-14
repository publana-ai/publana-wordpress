<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_I18n
{
    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'load']);
    }

    public function load(): void
    {
        load_plugin_textdomain(
            'publana-api',
            false,
            dirname(plugin_basename(PUBLANA_API_FILE)) . '/languages'
        );
    }
}