<?php
/*
Plugin Name: Sanyark Leave Management
Description: Headless frontend integration with Spring Boot Leave Management API
Version: 1.0
Author: Harsh
*/

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    if (!session_id()) {
        session_start();
    }
});

define('SLM_API_BASE_URL', 'http://localhost:8080');
define('SLM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SLM_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once SLM_PLUGIN_PATH . 'includes/class-slm-api.php';
require_once SLM_PLUGIN_PATH . 'includes/class-slm-auth.php';
require_once SLM_PLUGIN_PATH . 'includes/class-slm-shortcodes.php';
require_once SLM_PLUGIN_PATH . 'includes/class-slm-pages.php';

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'slm-style',
        SLM_PLUGIN_URL . 'assets/css/slm-style.css',
        [],
        '1.3.0'
    );

    wp_enqueue_script(
        'particles-js',
        'https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js',
        [],
        '2.0.0',
        true
    );

    wp_enqueue_script(
        'slm-app',
        SLM_PLUGIN_URL . 'assets/js/slm-app.js',
        ['particles-js'],
        '1.3.0',
        true
    );
});

/*
|--------------------------------------------------------------------------
| Render the global space background on every frontend page
|--------------------------------------------------------------------------
*/
add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }

    echo '<div id="slm-space-bg"><div id="particles-js"></div></div>';
}, 1);

new SLM_Shortcodes();
new SLM_Pages();