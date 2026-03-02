<?php
/*
 * Plugin Name: Advanced Typing Test Pro
 * Plugin URI:  https://github.com/rayhan-hosen/typing-test-wordpress-plugin
 * Description: A generalized, professional typing test plugin. Admin can add languages and contents, and each language generates a shortcode. Includes a dynamic certification system.
 * Version:     2.0.0
 * Author:      Rayhan Hosen
 * Author URI:  https://github.com/rayhan-hosen
 * License:     GPLv2 or later
 * Text Domain: typing-test
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('TYPING_TEST_VERSION', '2.0.0');
define('TYPING_TEST_FILE', __FILE__);
define('TYPING_TEST_PATH', plugin_dir_path(__FILE__));
define('TYPING_TEST_URL', plugin_dir_url(__FILE__));

// Load Includes
require_once TYPING_TEST_PATH . 'includes/class-post-types.php';
require_once TYPING_TEST_PATH . 'includes/class-settings.php';
require_once TYPING_TEST_PATH . 'includes/class-shortcode.php';

// Initialize
Typing_Test_Post_Types::init();
Typing_Test_Settings::init();
Typing_Test_Shortcode::init();

// Activation Hook
register_activation_hook(__FILE__, 'typing_test_activate');

function typing_test_activate() {
    Typing_Test_Post_Types::register_taxonomy();
    Typing_Test_Post_Types::register_post_type();
    flush_rewrite_rules();
    
    // Set default settings if not exists
    if (!get_option('typing_test_min_wpm')) {
        update_option('typing_test_min_wpm', 30);
    }
    if (!get_option('typing_test_min_duration')) {
        update_option('typing_test_min_duration', 60);
    }
    if (!get_option('typing_test_brand_name')) {
        update_option('typing_test_brand_name', 'Rayhan');
    }
}