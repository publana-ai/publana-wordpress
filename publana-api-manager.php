<?php
/**
 * Plugin Name: Publana API Manager
 * Plugin URI: https://support.publana.com/docs
 * Description: Professional API management solution for Publana.
 * Version: 1.8.2
 * Author: Publana
 * Author URI: https://publana.com
 * Text Domain: publana-api
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

define('PUBLANA_API_VERSION', '1.8.2');
define('PUBLANA_API_FILE', __FILE__);
define('PUBLANA_API_PATH', plugin_dir_path(__FILE__));
define('PUBLANA_API_URL', plugin_dir_url(__FILE__));

define('PUBLANA_API_TEXT_DOMAIN', 'publana-api');

/*
|--------------------------------------------------------------------------
| Load Classes
|--------------------------------------------------------------------------
*/

require_once PUBLANA_API_PATH . 'includes/helpers.php';

require_once PUBLANA_API_PATH . 'includes/class-plugin.php';
require_once PUBLANA_API_PATH . 'includes/class-i18n.php';
require_once PUBLANA_API_PATH . 'includes/class-assets.php';
require_once PUBLANA_API_PATH . 'includes/class-token-manager.php';
require_once PUBLANA_API_PATH . 'includes/class-auth.php';
require_once PUBLANA_API_PATH . 'includes/class-rest-api.php';
require_once PUBLANA_API_PATH . 'includes/class-admin.php';

/*
|--------------------------------------------------------------------------
| Activation
|--------------------------------------------------------------------------
*/

register_activation_hook(
    PUBLANA_API_FILE,
    ['Publana_Plugin', 'activate']
);

register_deactivation_hook(
    PUBLANA_API_FILE,
    ['Publana_Plugin', 'deactivate']
);

/*
|--------------------------------------------------------------------------
| Boot Plugin
|--------------------------------------------------------------------------
*/

add_action('plugins_loaded', function () {
    Publana_Plugin::boot();
});