<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_Admin
{
    /**
     * Token manager.
     */
    private Publana_Token_Manager $tokens;

    /**
     * Constructor.
     */
    public function __construct(Publana_Token_Manager $tokens)
    {
        $this->tokens = $tokens;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action(
            'admin_post_publana_generate_token',
            [$this, 'generate_token']
        );

        add_action(
            'admin_post_publana_revoke_token',
            [$this, 'revoke_token']
        );
    }

    /**
     * Register admin menu.
     */
    public function register_menu(): void
    {
        add_menu_page(
            'Publana API Manager',
            'Publana',
            'manage_options',
            'publana-api-manager',
            [$this, 'dashboard'],
            publana_url('logo.png'),
            65
        );

        add_submenu_page(
            'publana-api-manager',
            __('Documentation', PUBLANA_API_TEXT_DOMAIN),
            __('Documentation', PUBLANA_API_TEXT_DOMAIN),
            'manage_options',
            'publana-api-docs',
            [$this, 'documentation']
        );
    }

    /**
     * Dashboard.
     */
    public function dashboard(): void
    {
        $tokens = $this->tokens->all();

        include PUBLANA_API_PATH . 'templates/admin-page.php';
    }

    /**
     * Documentation.
     */
    public function documentation(): void
    {
        include PUBLANA_API_PATH . 'templates/documentation.php';
    }

    /**
     * Generate token.
     */
    public function generate_token(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission.', PUBLANA_API_TEXT_DOMAIN));
        }

        check_admin_referer('publana_generate_token');

        $result = $this->tokens->generate();

        set_transient(
            'publana_new_token_' . get_current_user_id(),
            $result['plain'],
            60
        );

        wp_safe_redirect(
            admin_url('admin.php?page=publana-api-manager')
        );

        exit;
    }

    /**
     * Revoke token.
     */
    public function revoke_token(): void
    {
        check_admin_referer('publana_revoke_token');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission.', PUBLANA_API_TEXT_DOMAIN));
        }

        $id = sanitize_text_field($_POST['token'] ?? '');

        $this->tokens->revoke($id);

        wp_safe_redirect(admin_url('admin.php?page=publana-api-manager'));
        exit;
    }
}