<?php
if (!defined('ABSPATH')) {
    exit;
}

class Typing_Test_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=typing_content',
            __('Typing Test Settings', 'typing-test'),
            __('Settings', 'typing-test'),
            'manage_options',
            'typing-test-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('typing_test_settings', 'typing_test_certificate_issuer', 'sanitize_text_field');
        register_setting('typing_test_settings', 'typing_test_certificate_website', 'esc_url_raw');
        register_setting('typing_test_settings', 'typing_test_certificate_filename', 'sanitize_text_field');
        register_setting('typing_test_settings', 'typing_test_min_wpm', 'intval');
        register_setting('typing_test_settings', 'typing_test_min_duration', 'intval');
        register_setting('typing_test_settings', 'typing_test_brand_name', 'sanitize_text_field');

        add_settings_section('typing_test_cert_section', __('Certificate Settings', 'typing-test'), null, 'typing-test-settings');

        add_settings_field('typing_test_brand_name', __('Brand Name (Top of Certificate)', 'typing-test'), [__CLASS__, 'render_text_field'], 'typing-test-settings', 'typing_test_cert_section', ['label_for' => 'typing_test_brand_name']);
        add_settings_field('typing_test_certificate_issuer', __('Issuer Label', 'typing-test'), [__CLASS__, 'render_text_field'], 'typing-test-settings', 'typing_test_cert_section', ['label_for' => 'typing_test_certificate_issuer']);
        add_settings_field('typing_test_certificate_website', __('Issuer Website', 'typing-test'), [__CLASS__, 'render_text_field'], 'typing-test-settings', 'typing_test_cert_section', ['label_for' => 'typing_test_certificate_website']);
        add_settings_field('typing_test_certificate_filename', __('Download Filename (no extension)', 'typing-test'), [__CLASS__, 'render_text_field'], 'typing-test-settings', 'typing_test_cert_section', ['label_for' => 'typing_test_certificate_filename']);
        add_settings_field('typing_test_min_wpm', __('Minimum WPM to Pass', 'typing-test'), [__CLASS__, 'render_number_field'], 'typing-test-settings', 'typing_test_cert_section', ['label_for' => 'typing_test_min_wpm', 'default' => 30]);
        add_settings_field('typing_test_min_duration', __('Minimum Duration (Seconds) to Pass', 'typing-test'), [__CLASS__, 'render_number_field'], 'typing-test-settings', 'typing_test_cert_section', ['label_for' => 'typing_test_min_duration', 'default' => 60]);
    }

    public static function render_text_field($args) {
        $option = get_option($args['label_for']);
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="' . esc_attr($option) . '" class="regular-text" />';
    }

    public static function render_number_field($args) {
        $option = get_option($args['label_for'], $args['default'] ?? 0);
        echo '<input type="number" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="' . esc_attr($option) . '" class="small-text" />';
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Typing Test Settings', 'typing-test'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('typing_test_settings');
                do_settings_sections('typing-test-settings');
                submit_button();
                ?>
            </form>

            <hr>
            <h2><?php echo esc_html__('Shortcodes', 'typing-test'); ?></h2>
            <p><?php echo esc_html__('Use these shortcodes to display the typing test for specific languages. You can find language slugs under Typing Test > Languages.', 'typing-test'); ?></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Language Name', 'typing-test'); ?></th>
                        <th><?php _e('Slug', 'typing-test'); ?></th>
                        <th><?php _e('Shortcode', 'typing-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $languages = get_terms(['taxonomy' => 'typing_language', 'hide_empty' => false]);
                    if (!empty($languages)) {
                        foreach ($languages as $lang) {
                            echo '<tr>';
                            echo '<td>' . esc_html($lang->name) . '</td>';
                            echo '<td><code>' . esc_html($lang->slug) . '</code></td>';
                            echo '<td><code>[typing_test language="' . esc_attr($lang->slug) . '"]</code></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3">' . __('No languages found. Add some under Typing Test > Languages.', 'typing-test') . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
