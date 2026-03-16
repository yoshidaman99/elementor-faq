<?php
/**
 * Plugin Name: Elementor FAQ
 * Plugin URI: https://yosh.tools/elementor-faq
 * Description: A powerful Elementor FAQ widget with accordion, search, categories, and full responsive controls.
 * Version: 1.0.1
 * Author: Yosh Tools
 * Author URI: https://yosh.tools
 * Text Domain: elementor-faq
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ELEMENTOR_FAQ_VERSION', '1.0.1');
define('ELEMENTOR_FAQ_FILE', __FILE__);
define('ELEMENTOR_FAQ_DIR', plugin_dir_path(__FILE__));
define('ELEMENTOR_FAQ_URL', plugin_dir_url(__FILE__));
define('ELEMENTOR_FAQ_BASENAME', plugin_basename(__FILE__));

spl_autoload_register('elementor_faq_autoloader');

function elementor_faq_autoloader($class)
{
    $prefix = 'Elementor_FAQ\\';
    $base_dir = ELEMENTOR_FAQ_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
}

function elementor_faq()
{
    return \Elementor_FAQ\Core\Plugin::get_instance();
}

function elementor_faq_activate()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    flush_rewrite_rules();

    $version = get_option('elementor_faq_version', '0.0.0');

    if (version_compare($version, ELEMENTOR_FAQ_VERSION, '<')) {
        update_option('elementor_faq_version', ELEMENTOR_FAQ_VERSION);
    }
}

function elementor_faq_deactivate()
{
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'elementor_faq_activate');
register_deactivation_hook(__FILE__, 'elementor_faq_deactivate');

add_action('plugins_loaded', 'elementor_faq', 5);
