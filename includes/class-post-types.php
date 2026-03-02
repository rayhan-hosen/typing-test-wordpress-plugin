<?php
if (!defined('ABSPATH')) {
    exit;
}

class Typing_Test_Post_Types {
    public static function init() {
        add_action('init', [__CLASS__, 'register_taxonomy']);
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('add_meta_boxes', [__CLASS__, 'add_difficulty_meta_box']);
        add_action('save_post', [__CLASS__, 'save_difficulty_meta_box']);

        // Add shortcode column to taxonomy
        add_filter('manage_edit-typing_language_columns', [__CLASS__, 'add_shortcode_column']);
        add_action('manage_typing_language_custom_column', [__CLASS__, 'render_shortcode_column'], 10, 3);
    }

    public static function add_shortcode_column($columns) {
        $columns['shortcode'] = __('Shortcode', 'typing-test');
        return $columns;
    }

    public static function render_shortcode_column($content, $column_name, $term_id) {
        if ($column_name === 'shortcode') {
            $term = get_term($term_id, 'typing_language');
            return '<code>[typing_test language="' . esc_attr($term->slug) . '"]</code>';
        }
        return $content;
    }

    public static function register_taxonomy() {
        $labels = [
            'name'              => _x('Typing Languages', 'taxonomy general name', 'typing-test'),
            'singular_name'     => _x('Typing Language', 'taxonomy singular name', 'typing-test'),
            'search_items'      => __('Search Typing Languages', 'typing-test'),
            'all_items'         => __('All Typing Languages', 'typing-test'),
            'parent_item'       => __('Parent Typing Language', 'typing-test'),
            'parent_item_colon' => __('Parent Typing Language:', 'typing-test'),
            'edit_item'         => __('Edit Typing Language', 'typing-test'),
            'update_item'       => __('Update Typing Language', 'typing-test'),
            'add_new_item'      => __('Add New Typing Language', 'typing-test'),
            'new_item_name'     => __('New Typing Language Name', 'typing-test'),
            'menu_name'         => __('Languages', 'typing-test'),
        ];

        register_taxonomy('typing_language', ['typing_content'], [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'typing-language'],
        ]);
    }

    public static function register_post_type() {
        $labels = [
            'name'               => _x('Typing Contents', 'post type general name', 'typing-test'),
            'singular_name'      => _x('Typing Content', 'post type singular name', 'typing-test'),
            'menu_name'          => _x('Typing Test', 'admin menu', 'typing-test'),
            'name_admin_bar'     => _x('Typing Content', 'add new on admin bar', 'typing-test'),
            'add_new'            => _x('Add New', 'typing content', 'typing-test'),
            'add_new_item'       => __('Add New Typing Content', 'typing-test'),
            'new_item'           => __('New Typing Content', 'typing-test'),
            'edit_item'          => __('Edit Typing Content', 'typing-test'),
            'view_item'          => __('View Typing Content', 'typing-test'),
            'all_items'          => __('All Contents', 'typing-test'),
            'search_items'       => __('Search Typing Contents', 'typing-test'),
            'parent_item_colon'  => __('Parent Typing Contents:', 'typing-test'),
            'not_found'          => __('No typing contents found.', 'typing-test'),
            'not_found_in_trash' => __('No typing contents found in Trash.', 'typing-test'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'typing-content'],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 56,
            'supports'           => ['title', 'editor'],
            'menu_icon'          => 'dashicons-keyboard',
        ];

        register_post_type('typing_content', $args);
    }

    public static function add_difficulty_meta_box() {
        add_meta_box(
            'typing_difficulty',
            __('Difficulty Level', 'typing-test'),
            [__CLASS__, 'render_difficulty_meta_box'],
            'typing_content',
            'side',
            'default'
        );
    }

    public static function render_difficulty_meta_box($post) {
        $difficulty = get_post_meta($post->ID, '_typing_difficulty', true);
        wp_nonce_field('typing_difficulty_nonce', 'typing_difficulty_nonce');
        ?>
        <select name="typing_difficulty" id="typing_difficulty" class="postbox">
            <option value="easy" <?php selected($difficulty, 'easy'); ?>><?php _e('Easy', 'typing-test'); ?></option>
            <option value="medium" <?php selected($difficulty, 'medium'); ?>><?php _e('Medium', 'typing-test'); ?></option>
            <option value="hard" <?php selected($difficulty, 'hard'); ?>><?php _e('Hard', 'typing-test'); ?></option>
        </select>
        <?php
    }

    public static function save_difficulty_meta_box($post_id) {
        if (!isset($_POST['typing_difficulty_nonce']) || !wp_verify_nonce($_POST['typing_difficulty_nonce'], 'typing_difficulty_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['typing_difficulty'])) {
            update_post_meta($post_id, '_typing_difficulty', sanitize_text_field($_POST['typing_difficulty']));
        }
    }
}
