<?php
if (!defined('ABSPATH')) {
    exit;
}

class Typing_Test_Shortcode {
    public static function init() {
        add_shortcode('typing_test', [__CLASS__, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
    }

    public static function register_assets() {
        wp_register_style('typing-test-style', plugins_url('assets/css/typing-test.css', TYPING_TEST_FILE), [], '1.2.0');
        wp_register_script('typing-test-script', plugins_url('assets/js/typing-test.js', TYPING_TEST_FILE), ['jquery'], '1.2.0', true);
    }

    public static function render_shortcode($atts) {
        $atts = shortcode_atts([
            'language' => 'english',
        ], $atts, 'typing_test');

        $lang_slug = sanitize_text_field($atts['language']);

        // Fetch passages grouped by difficulty for this language
        $passages = self::get_passages_by_language($lang_slug);
        
        if (empty($passages['easy']) && empty($passages['medium']) && empty($passages['hard'])) {
            return '<div class="typing-test-error">' . sprintf(__('No passages found for language: %s. Please add some in the admin panel.', 'typing-test'), esc_html($lang_slug)) . '</div>';
        }

        // Enqueue assets
        wp_enqueue_style('typing-test-style');
        wp_enqueue_script('typing-test-script');

        // Localize data
        $cert_settings = [
            'minWpm'         => (int) get_option('typing_test_min_wpm', 30),
            'minDuration'    => (int) get_option('typing_test_min_duration', 60),
            'issuerLabel'     => get_option('typing_test_certificate_issuer', 'Rayhan'),
            'issuerWebsite'   => get_option('typing_test_certificate_website', 'www.example.com'),
            'downloadFilename'=> get_option('typing_test_certificate_filename', 'Typing-Certificate'),
            'brandName'       => get_option('typing_test_brand_name', 'Rayhan'),
            'html2canvasCdn'  => 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js',
            'jspdfCdn'        => 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js',
        ];

        wp_localize_script('typing-test-script', 'bttData', [
            'ajaxUrl'              => admin_url('admin-ajax.php'),
            'language'             => $lang_slug,
            'passagesByDifficulty' => $passages,
            'durations'            => [30, 60, 120, 180, 300],
            'certificate'          => $cert_settings,
        ]);

        // Capture template output
        ob_start();
        include plugin_dir_path(TYPING_TEST_FILE) . 'templates/typing-test-container.php';
        return ob_get_clean();
    }

    private static function get_passages_by_language($lang_slug) {
        $passages = [
            'easy'   => [],
            'medium' => [],
            'hard'   => [],
        ];

        $args = [
            'post_type'      => 'typing_content',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => 'typing_language',
                    'field'    => 'slug',
                    'terms'    => $lang_slug,
                ],
            ],
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $diff = get_post_meta($id, '_typing_difficulty', true) ?: 'medium';
                
                $passages[$diff][] = [
                    'id'      => 'p_' . $id,
                    'title'   => get_the_title(),
                    'content' => strip_shortcodes(wp_strip_all_tags(get_the_content())),
                ];
            }
            wp_reset_postdata();
        }

        return $passages;
    }
}
