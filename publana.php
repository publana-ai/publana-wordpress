<?php
/**
 * Plugin Name: Publana API Manager
 * Plugin URI: https://support.publana.com/docs
 * Description: Professional API management solution for Publana - Create and manage posts via secure REST API endpoints with Bearer token authentication.
 * Version: 1.7
 * Author: Publana
 * Author URI: https://publana.com
 * Text Domain: publana-api
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Publana_APIManager {

    private $brand_name = 'Publana';
    private $brand_color = '#000000';
    private $secondary_color = '#000000';
    private $accent_color = '#8A2BE2';
    private $logo_url;
    private $option_key = 'publana_api_tokens';
    private $namespace = 'publana/v1';
    private $text_domain = 'publana-api';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_publana_textdomain'));
        add_action('rest_api_init', array($this, 'register_publana_routes'));
        add_action('admin_menu', array($this, 'add_publana_admin_menu'));
        add_action('admin_init', array($this, 'handle_publana_form_submissions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_publana_admin_assets'));
        add_action('admin_head', function() {
            echo '<style>
    .toplevel_page_publana-api-manager .wp-menu-image img {
        padding: unset !important;
        width: 20px !important;
        height: 20px !important;
    }
    .toplevel_page_publana-api-manager .wp-menu-image {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    </style>';
        });

        $this->logo_url = plugin_dir_url(__FILE__) . 'logo.png';
    }

    /**
     * Load plugin textdomain for localization
     */
    public function load_publana_textdomain() {
        load_plugin_textdomain(
            $this->text_domain,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Register Publana REST API routes
     */
    public function register_publana_routes() {
        register_rest_route($this->namespace, '/posts', [
            'methods'  => 'POST',
            'callback' => array($this, 'publana_create_post'),
            'permission_callback' => array($this, 'publana_authenticate_request'),
        ]);

        // Additional endpoint for token validation
        register_rest_route($this->namespace, '/validate', [
            'methods'  => 'GET',
            'callback' => array($this, 'publana_validate_token'),
            'permission_callback' => array($this, 'publana_authenticate_request'),
        ]);
    }

    /**
     * Authenticate using Bearer token
     */
    public function publana_authenticate_request($request) {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return new WP_Error(
                'publana_unauthorized',
                __('Authentication_required', 'publana-api'),
                ['status' => 401]
            );
        }

        $token = trim($matches[1]);
        $valid_tokens = get_option($this->option_key, []);

        if (!in_array($token, $valid_tokens, true)) {
            return new WP_Error(
                'publana_invalid_token',
                __('Invalid_or_expired_authentication', 'publana-api'),
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * Create a post via Publana API
     */
    public function publana_create_post($request) {
        $params = $request->get_json_params();

        $title = sanitize_text_field($params['title'] ?? '');
        $content = wp_kses_post($params['content'] ?? '');
        $status = sanitize_text_field($params['status'] ?? 'publish');
        $author = absint($params['author'] ?? 1);

        if (empty($title)) {
            return new WP_Error(
                'publana_missing_title',
                __('Post_title_is_required', 'publana-api'),
                ['status' => 400]
            );
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => $status,
            'post_author'  => $author,
            'post_type'    => 'post',
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'publana_creation_failed',
                sprintf(__('Failed_to_create_post', 'publana-api'), $post_id->get_error_message()),
                ['status' => 500]
            );
        }

        return [
            'success' => true,
            'message' => __('Post_created_successfully', 'publana-api'),
            'data' => [
                'post_id' => $post_id,
                'permalink' => get_permalink($post_id),
                'edit_link' => get_edit_post_link($post_id, 'json')
            ]
        ];
    }

    /**
     * Validate token endpoint
     */
    public function publana_validate_token($request) {
        return [
            'success' => true,
            'message' => __('Token_is_valid', 'publana-api'),
            'brand' => $this->brand_name,
            'timestamp' => current_time('c')
        ];
    }

    /**
     * Add Publana admin menu
     */
    public function add_publana_admin_menu() {
        $menu_title = $this->brand_name;

        // RTL support for menu title in Arabic and Farsi
        if (in_array($this->get_user_language(), ['ar', 'fa'])) {
            $menu_title = $this->brand_name . '‎'; // Add RTL mark if needed
        }

        add_menu_page(
            $this->brand_name . ' ' . __('API_Manager', 'publana-api'),
            $menu_title,
            'manage_options',
            'publana-api-manager',
            array($this, 'render_publana_admin_interface'),
            $this->logo_url,
            65
        );

        add_submenu_page(
            'publana-api-manager',
            __('API_Documentation', 'publana-api') . ' - ' . $this->brand_name,
            __('Documentation', 'publana-api'),
            'manage_options',
            'publana-api-docs',
            array($this, 'render_publana_documentation')
        );
    }

    /**
     * Get user's language preference
     */
    private function get_user_language() {
        $locale = get_user_locale();
        return explode('_', $locale)[0]; // Returns 'en', 'fa', 'ru', 'ar'
    }

    /**
     * Handle admin form submissions
     */
    public function handle_publana_form_submissions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Generate new token
        if (isset($_POST['publana_generate_token']) && check_admin_referer('publana_generate_token')) {
            $token = $this->generate_secure_token();
            $tokens = get_option($this->option_key, []);
            $tokens[] = $token;
            update_option($this->option_key, $tokens);

            add_action('admin_notices', function() use ($token) {
                echo '<div class="notice notice-success is-dismissible publana-notice"><p>';
                echo '<strong>' . $this->brand_name . ' ' . __('API', 'publana-api') . ':</strong> ';
                echo __('New_token_generated_successfully', 'publana-api');
                echo '<br><code class="publana-token-display">' . esc_html($token) . '</code>';
                echo '</p></div>';
            });
        }

        // Revoke token
        if (isset($_POST['publana_revoke_token']) && check_admin_referer('publana_revoke_token')) {
            $token_to_revoke = sanitize_text_field($_POST['publana_token_to_revoke']);
            $tokens = array_filter(get_option($this->option_key, []), function($token) use ($token_to_revoke) {
                return $token !== $token_to_revoke;
            });
            update_option($this->option_key, $tokens);

            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible publana-notice"><p>';
                echo '<strong>' . $this->brand_name . ' ' . __('API', 'publana-api') . ':</strong> ';
                echo __('Token_revoked_successfully', 'publana-api');
                echo '</p></div>';
            });
        }
    }

    /**
     * Generate secure token
     */
    private function generate_secure_token() {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            return wp_generate_password(64, false);
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_publana_admin_assets($hook) {
        if (strpos($hook, 'publana-api') === false) {
            return;
        }

        $language = $this->get_user_language();
        $is_rtl = in_array($language, ['ar', 'fa']);

        wp_enqueue_style('publana-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.7');

        // Add dynamic CSS for brand colors
        wp_add_inline_style('publana-admin-css', "
            :root {
                --publana-primary: {$this->brand_color};
                --publana-secondary: {$this->secondary_color};
                --publana-accent: {$this->accent_color};
            }
        ");

        // Add RTL class to body if needed
        if ($is_rtl) {
            wp_add_inline_script('jquery', "
                jQuery(document).ready(function($) {
                    $('body').addClass('publana-rtl-support');
                });
            ");
        }

    }

    /**
     * Render main admin interface
     */
    public function render_publana_admin_interface() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You_do_not_have_sufficient_permissions_to_access_this_page', 'publana-api'));
        }

        $tokens = get_option($this->option_key, []);
        $language = $this->get_user_language();
        $is_rtl = in_array($language, ['ar', 'fa']);
        ?>
        <div class="wrap publana-admin <?php echo $is_rtl ? 'publana-rtl-support' : ''; ?>">
            <!-- Header Section -->
            <div class="publana-header">
                <div class="publana-header-content">
                    <div class="publana-brand">
                        <div class="publana-logo">
                            <img class="publana-logo-icon" src="<?php printf($this->logo_url) ?>" alt="logo">
                            <h1><?php echo $this->brand_name; ?> <span class="publana-subtitle"><?php _e('API_Manager', 'publana-api'); ?></span></h1>
                        </div>
                        <p class="publana-description"><?php printf(__('Secure_API_token_management_for_', 'publana-api'), $this->brand_name); ?></p>
                    </div>
                    <div class="publana-stats">
                        <div class="publana-stat-card">
                            <div class="publana-stat-number"><?php echo count($tokens); ?></div>
                            <div class="publana-stat-label"><?php _e('Active_Tokens', 'publana-api'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="publana-quick-actions">
                <div class="publana-action-card publana-generate-card">
                    <div class="publana-action-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 6V10L13 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <div class="publana-action-content">
                        <h3><?php _e('Generate_New_Token', 'publana-api'); ?></h3>
                        <p><?php _e('Create_a_new_secure_API_token_for_external_applications', 'publana-api'); ?></p>
                        <form method="post" class="publana-action-form">
                            <?php wp_nonce_field('publana_generate_token'); ?>
                            <button type="submit" name="publana_generate_token" class="publana-btn publana-btn-primary">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 1V15M1 8H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <?php _e('Generate_Token', 'publana-api'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="publana-action-card">
                    <div class="publana-action-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 13L13 10L10 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 10H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <rect x="2.5" y="2.5" width="15" height="15" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <div class="publana-action-content">
                        <h3><?php _e('API_Information', 'publana-api'); ?></h3>
                        <div class="publana-api-info">
                            <div class="publana-api-field">
                                <label><?php _e('Endpoint', 'publana-api'); ?>:</label>
                                <code><?php echo rest_url($this->namespace . '/posts'); ?></code>
                            </div>
                            <div class="publana-api-field">
                                <label><?php _e('Authentication', 'publana-api'); ?>:</label>
                                <span class="publana-tag">Bearer Token</span>
                            </div>
                            <div class="publana-api-field">
                                <label><?php _e('Method', 'publana-api'); ?>:</label>
                                <span class="publana-tag publana-tag-method">POST</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tokens Section -->
            <div class="publana-section">
                <div class="publana-section-header">
                    <h2><?php _e('Active_Tokens', 'publana-api'); ?></h2>
                    <div class="publana-section-actions">
                        <span class="publana-token-count"><?php echo count($tokens); ?> <?php _e('tokens', 'publana-api'); ?></span>
                    </div>
                </div>

                <?php if (empty($tokens)): ?>
                    <div class="publana-empty-state">
                        <div class="publana-empty-icon">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M32 16H16M32 24H16M32 32H24" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <rect x="6" y="6" width="36" height="36" rx="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3><?php _e('No_API_tokens_yet', 'publana-api'); ?></h3>
                        <p><?php printf(__('Generate_your_first_token_to_start_using_the_', 'publana-api'), $this->brand_name); ?></p>
                    </div>
                <?php else: ?>
                    <div class="publana-tokens-grid">
                        <?php foreach ($tokens as $index => $token): ?>
                            <div class="publana-token-card">
                                <div class="publana-token-header">
                                    <div class="publana-token-badge">
                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="6" cy="6" r="5.5" fill="#10b981" stroke="#10b981"/>
                                        </svg>
                                        <?php _e('Active', 'publana-api'); ?>
                                    </div>
                                    <div class="publana-token-actions">
                                        <button type="button" class="publana-btn-icon" onclick="publanaCopyToken(<?php echo $index; ?>)" title="<?php _e('Copy', 'publana-api'); ?>">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="3.5" y="3.5" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.2"/>
                                                <path d="M5.5 1.5H1.5V5.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="publana-btn-icon" onclick="publanaToggleToken(<?php echo $index; ?>)" title="<?php _e('Toggle_Visibility', 'publana-api'); ?>">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7 3C4.5 3 2.5 4.5 1 7C2.5 9.5 4.5 11 7 11C9.5 11 11.5 9.5 13 7C11.5 4.5 9.5 3 7 3Z" stroke="currentColor" stroke-width="1.2"/>
                                                <circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/>
                                            </svg>
                                        </button>
                                        <form method="post" class="publana-inline-form">
                                            <?php wp_nonce_field('publana_revoke_token'); ?>
                                            <input type="hidden" name="publana_token_to_revoke" value="<?php echo esc_attr($token); ?>">
                                            <button type="submit" name="publana_revoke_token" class="publana-btn-icon publana-btn-danger" title="<?php _e('Revoke', 'publana-api'); ?>" onclick="return confirm('<?php _e('Are_you_sure_you_want_to_revoke_this_token', 'publana-api'); ?>')">
                                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11 3L3 11M3 3L11 11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="publana-token-content">
                                    <input type="password"
                                           value="<?php echo esc_attr($token); ?>"
                                           readonly
                                           class="publana-token-field"
                                           id="publana-token-<?php echo $index; ?>">
                                </div>
                                <div class="publana-token-footer">
                                    <span class="publana-token-id">#<?php echo $index + 1; ?></span>
                                    <span class="publana-token-length"><?php echo strlen($token); ?> <?php _e('chars', 'publana-api'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            function publanaCopyToken(index) {
                const field = document.getElementById('publana-token-' + index);
                field.type = 'text';
                field.select();
                document.execCommand('copy');
                field.type = 'password';

                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'publana-toast';
                toast.innerHTML = '<?php _e('Token_copied_to_clipboard!', 'publana-api'); ?>';
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.add('publana-toast-show');
                    setTimeout(() => {
                        toast.classList.remove('publana-toast-show');
                        setTimeout(() => document.body.removeChild(toast), 300);
                    }, 2000);
                }, 100);
            }

            function publanaToggleToken(index) {
                const field = document.getElementById('publana-token-' + index);
                const btn = event.currentTarget;

                if (field.type === 'password') {
                    field.type = 'text';
                    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L12 12M5.5 5.5C5.5 6.5 6.5 7.5 7.5 7.5M8.5 5.5C8.5 4.5 7.5 3.5 6.5 3.5L9.5 6.5C9.5 6.5 9.5 6.5 9.5 6.5C9.5 5.5 8.5 4.5 7.5 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M4 7C4 7 5 9 7 9C7.5 9 8 8.5 8.5 8" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>';
                } else {
                    field.type = 'password';
                    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 3C4.5 3 2.5 4.5 1 7C2.5 9.5 4.5 11 7 11C9.5 11 11.5 9.5 13 7C11.5 4.5 9.5 3 7 3Z" stroke="currentColor" stroke-width="1.2"/><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/></svg>';
                }
            }
        </script>
        <?php
    }

    /**
     * Render documentation page
     */
    public function render_publana_documentation() {
        $language = $this->get_user_language();
        $is_rtl = in_array($language, ['ar', 'fa']);
        ?>
        <div class="wrap publana-admin <?php echo $is_rtl ? 'publana-rtl-support' : ''; ?>">
            <div class="publana-header">
                <div class="publana-header-content">
                    <div class="publana-brand">
                        <div class="publana-logo">
                            <img class="publana-logo-icon" src="<?php printf($this->logo_url) ?>" alt="logo">
                            <h1><?php echo $this->brand_name; ?> <span class="publana-subtitle"><?php _e('API_Documentation', 'publana-api'); ?></span></h1>
                        </div>
                        <p class="publana-description"><?php _e('Complete_API_reference_and_integration_guide', 'publana-api'); ?></p>
                    </div>
                </div>
            </div>

            <div class="publana-docs-grid">
                <div class="publana-docs-main">
                    <div class="publana-section">
                        <h2><?php _e('API_Endpoints', 'publana-api'); ?></h2>

                        <div class="publana-endpoint-card">
                            <div class="publana-endpoint-header">
                                <span class="publana-endpoint-method publana-method-post">POST</span>
                                <code class="publana-endpoint-url"><?php echo rest_url($this->namespace . '/posts'); ?></code>
                            </div>
                            <div class="publana-endpoint-content">
                                <h4><?php _e('Create_Post', 'publana-api'); ?></h4>

                                <h5><?php _e('Headers', 'publana-api'); ?>:</h5>
                                <div class="publana-code-block">
                                    <pre><code>Authorization: Bearer your_api_token_here
Content-Type: application/json</code></pre>
                                </div>

                                <h5><?php _e('Request_Body', 'publana-api'); ?>:</h5>
                                <div class="publana-code-block">
                                    <pre><code>{
    "title": "<?php _e('Your_Post_Title', 'publana-api'); ?>",
    "content": "<?php _e('Your_post_content_with_HTML_support', 'publana-api'); ?>",
    "status": "publish",
    "author": 1
}</code></pre>
                                </div>

                                <h5><?php _e('Success_Response', 'publana-api'); ?>:</h5>
                                <div class="publana-code-block">
                                    <pre><code>{
    "success": true,
    "message": "<?php _e('Post_created_successfully', 'publana-api'); ?>",
    "data": {
        "post_id": 123,
        "permalink": "https://yoursite.com/your-post",
        "edit_link": "https://yoursite.com/wp-admin/post.php?post=123&action=edit"
    }
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="publana-docs-sidebar">
                    <div class="publana-section">
                        <h3><?php _e('Quick_Links', 'publana-api'); ?></h3>
                        <div class="publana-sidebar-links">
                            <a href="<?php echo admin_url('admin.php?page=publana-api-manager'); ?>" class="publana-sidebar-link">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 2L2 6L6 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2 6H10C12 6 14 8 14 10C14 12 12 14 10 14H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                <?php _e('Back_to_API_Manager', 'publana-api'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="publana-section">
                        <h3><?php _e('Available_Languages', 'publana-api'); ?></h3>
                        <div class="publana-languages">
                            <?php
                            $languages = [
                                'en' => ['name' => __('English', 'publana-api'), 'flag' => '🇺🇸'],
                                'fa' => ['name' => __('Farsi', 'publana-api'), 'flag' => '🇮🇷'],
                                'ru' => ['name' => __('Russian', 'publana-api'), 'flag' => '🇷🇺'],
                                'ar' => ['name' => __('Arabic', 'publana-api'), 'flag' => '🇸🇦']
                            ];
                            $current_lang = $this->get_user_language();
                            ?>
                            <?php foreach ($languages as $code => $lang): ?>
                                <div class="publana-language-item <?php echo $code === $current_lang ? 'publana-language-active' : ''; ?>">
                                    <span class="publana-language-flag"><?php echo $lang['flag']; ?></span>
                                    <span class="publana-language-name"><?php echo $lang['name']; ?></span>
                                    <?php if ($code === $current_lang): ?>
                                        <span class="publana-language-badge"><?php _e('Current', 'publana-api'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the Publana API Manager
new Publana_APIManager();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    if (!get_option('publana_api_tokens')) {
        update_option('publana_api_tokens', []);
    }
});

// Create the CSS file
function publana_create_css_file() {
    $css_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }

    $css_content = '
    .publana-admin {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    .publana-header {
        background: linear-gradient(135deg, var(--publana-primary) 0%, var(--publana-secondary) 100%);
        color: white;
        padding: 2rem 0;
        margin: -20px -20px 2rem -20px;
    }

    .publana-header-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .publana-brand h1 {
        color: white;
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
    }

    .publana-subtitle {
        opacity: 0.9;
        font-weight: 400;
    }

    .publana-logo {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .publana-logo-icon {
        width: 40px;
        height: 40px;
    }

    .publana-description {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    .publana-stats {
        display: flex;
        gap: 1rem;
    }

    .publana-stat-card {
        background: rgba(255,255,255,0.1);
        padding: 1rem 1.5rem;
        border-radius: 12px;
        text-align: center;
        backdrop-filter: blur(10px);
    }

    .publana-stat-number {
        font-size: 2rem;
        font-weight: bold;
        line-height: 1;
    }

    .publana-stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .publana-quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .publana-action-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        transition: all 0.2s ease;
    }

    .publana-action-card:hover {
        border-color: var(--publana-primary);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .publana-generate-card {
        border-left: 4px solid var(--publana-primary);
    }

    .publana-action-icon {
        width: 48px;
        height: 48px;
        background: var(--publana-primary);
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .publana-action-content {
        flex: 1;
    }

    .publana-action-content h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .publana-action-content p {
        margin: 0 0 1rem 0;
        color: #6b7280;
    }

    .publana-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: white;
        color: #374151;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .publana-btn-primary {
        background: var(--publana-primary);
        border-color: var(--publana-primary);
        color: white;
    }

    .publana-btn-primary:hover {
        background: var(--publana-secondary);
        border-color: var(--publana-secondary);
    }

    .publana-btn-icon {
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: white;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .publana-btn-icon:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .publana-btn-danger {
        color: #dc2626;
        border-color: #fecaca;
    }

    .publana-btn-danger:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .publana-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .publana-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .publana-section-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .publana-token-count {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .publana-empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6b7280;
    }

    .publana-empty-icon {
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .publana-empty-state h3 {
        margin: 0 0 0.5rem 0;
        color: #374151;
    }

    .publana-tokens-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .publana-token-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        transition: all 0.2s ease;
    }

    .publana-token-card:hover {
        border-color: var(--publana-primary);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .publana-token-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .publana-token-badge {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.5rem;
        background: #f0fdf4;
        color: #166534;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .publana-token-actions {
        display: flex;
        gap: 0.25rem;
    }

    .publana-token-content {
        margin-bottom: 0.75rem;
    }

    .publana-token-field {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #f9fafb;
        font-family: "Courier New", monospace;
        font-size: 0.75rem;
        color: #374151;
    }

    .publana-token-footer {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        color: #6b7280;
    }

    .publana-api-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .publana-api-field {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .publana-api-field label {
        font-weight: 500;
        color: #374151;
    }

    .publana-tag {
        padding: 0.25rem 0.5rem;
        background: #f3f4f6;
        color: #374151;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .publana-tag-method {
        background: #ecfdf5;
        color: #065f46;
    }

    .publana-toast {
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: #065f46;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 10000;
    }

    .publana-toast-show {
        transform: translateX(0);
    }

    .publana-docs-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .publana-endpoint-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .publana-endpoint-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .publana-endpoint-method {
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .publana-method-post {
        background: #ecfdf5;
        color: #065f46;
    }

    .publana-endpoint-url {
        font-family: "Courier New", monospace;
        font-size: 0.875rem;
        color: #374151;
    }

    .publana-endpoint-content {
        padding: 1.5rem;
    }

    .publana-endpoint-content h4 {
        margin: 0 0 1rem 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .publana-endpoint-content h5 {
        margin: 1.5rem 0 0.5rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
    }

    .publana-code-block {
        background: #1f2937;
        border-radius: 6px;
        overflow: hidden;
    }

    .publana-code-block pre {
        margin: 0;
        padding: 1rem;
        overflow-x: auto;
    }

    .publana-code-block code {
        color: #e5e7eb;
        font-family: "Courier New", monospace;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .publana-sidebar-links {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .publana-sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        color: #374151;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .publana-sidebar-link:hover {
        background: var(--publana-primary);
        color: white;
    }

    .publana-languages {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .publana-language-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border-radius: 6px;
        position: relative;
    }

    .publana-language-active {
        background: #eff6ff;
        border: 1px solid var(--publana-primary);
    }

    .publana-language-flag {
        font-size: 1.25rem;
    }

    .publana-language-name {
        flex: 1;
        font-weight: 500;
    }

    .publana-language-badge {
        padding: 0.25rem 0.5rem;
        background: var(--publana-primary);
        color: white;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .publana-notice {
        border-left: 4px solid var(--publana-primary);
    }
    .publana-notice * {
        color: #000000;
    }

    .publana-token-display {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        padding: 12px;
        border-radius: 6px;
        font-family: "Courier New", monospace;
        font-size: 13px;
        margin-top: 8px;
        display: block;
        word-break: break-all;
    }

    /* RTL Support */
    .publana-rtl-support .publana-header-content,
    .publana-rtl-support .publana-section-header,
    .publana-rtl-support .publana-api-field,
    .publana-rtl-support .publana-token-header,
    .publana-rtl-support .publana-token-footer {
        flex-direction: row-reverse;
    }

    .publana-rtl-support .publana-logo {
        flex-direction: row-reverse;
    }

    .publana-rtl-support .publana-endpoint-header {
        flex-direction: row-reverse;
    }

    .publana-rtl-support .publana-sidebar-link,
    .publana-rtl-support .publana-language-item {
        flex-direction: row-reverse;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .publana-docs-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .publana-header-content {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .publana-quick-actions {
            grid-template-columns: 1fr;
        }

        .publana-tokens-grid {
            grid-template-columns: 1fr;
        }

        .publana-section-header {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
    }
    .toplevel_page_publana-api-manager .wp-menu-image img {
    padding: unset!important;
    width: 20px; height: 20px;}
    .toplevel_page_publana-api-manager .wp-menu-image {
    display: flex;
    align-items: center;
    justify-content: center;
    }
    ';

    file_put_contents($css_dir . '/admin.css', $css_content);
}

// Create CSS file on plugin activation
register_activation_hook(__FILE__, 'publana_create_css_file');

// Also create it when the plugin loads in case it doesn't exist
add_action('plugins_loaded', 'publana_create_css_file');