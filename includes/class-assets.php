<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_Assets
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * Load admin assets.
     */
    public function enqueue(string $hook): void
    {
        if (strpos($hook, 'publana-api') === false) {
            return;
        }

        wp_enqueue_style(
            'publana-admin',
            PUBLANA_API_URL . 'assets/css/admin.css',
            [],
            PUBLANA_API_VERSION
        );

        wp_enqueue_script(
            'publana-admin',
            PUBLANA_API_URL . 'assets/js/admin.js',
            [],
            PUBLANA_API_VERSION,
            true
        );

        wp_localize_script(
            'publana-admin',
            'Publana',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),

                'i18n' => [
                    'copy'     => __('Copy', PUBLANA_API_TEXT_DOMAIN),
                    'copied'   => __('Copied', PUBLANA_API_TEXT_DOMAIN),
                    'revoke'   => __('Revoke', PUBLANA_API_TEXT_DOMAIN),
                    'cancel'   => __('Cancel', PUBLANA_API_TEXT_DOMAIN),
                    'confirm'  => __('Are you sure?', PUBLANA_API_TEXT_DOMAIN),
                ],
            ]
        );
    }
}