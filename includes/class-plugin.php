<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_Plugin
{
    /**
     * Plugin instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Services.
     */
    private Publana_I18n $i18n;
    private Publana_Assets $assets;
    private Publana_Token_Manager $tokens;
    private Publana_Auth $auth;
    private Publana_Rest_API $rest;
    private Publana_Admin $admin;

    /**
     * Bootstrap plugin.
     */
    public static function boot(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->register_services();
    }

    /**
     * Register plugin services.
     */
    private function register_services(): void
    {
        // Localization
        $this->i18n = new Publana_I18n();

        // Assets
        $this->assets = new Publana_Assets();

        // Token manager
        $this->tokens = new Publana_Token_Manager();

        // Authentication
        $this->auth = new Publana_Auth($this->tokens);

        // REST API
        $this->rest = new Publana_Rest_API($this->auth);

        // Admin Panel
        $this->admin = new Publana_Admin($this->tokens);
    }

    /**
     * Activation hook.
     */
    public static function activate(): void
    {
        if (get_option('publana_api_tokens') === false) {
            add_option('publana_api_tokens', []);
        }
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate(): void
    {
        // Reserved for future use.
    }

    /**
     * Plugin instance.
     */
    public static function instance(): self
    {
        return self::$instance;
    }

    /**
     * Services
     */
    public function tokens(): Publana_Token_Manager
    {
        return $this->tokens;
    }

    public function auth(): Publana_Auth
    {
        return $this->auth;
    }

    public function admin(): Publana_Admin
    {
        return $this->admin;
    }

    public function rest(): Publana_Rest_API
    {
        return $this->rest;
    }

    public function assets(): Publana_Assets
    {
        return $this->assets;
    }

    public function i18n(): Publana_I18n
    {
        return $this->i18n;
    }
}