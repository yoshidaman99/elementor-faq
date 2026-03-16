<?php

namespace Elementor_FAQ\PostTypes;

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Post_Type
{
    public function register(): void
    {
        $labels = [
            'name'                  => __('FAQs', 'elementor-faq'),
            'singular_name'         => __('FAQ', 'elementor-faq'),
            'menu_name'             => __('FAQs', 'elementor-faq'),
            'name_admin_bar'        => __('FAQ', 'elementor-faq'),
            'add_new'               => __('Add New', 'elementor-faq'),
            'add_new_item'          => __('Add New FAQ', 'elementor-faq'),
            'new_item'              => __('New FAQ', 'elementor-faq'),
            'edit_item'             => __('Edit FAQ', 'elementor-faq'),
            'view_item'             => __('View FAQ', 'elementor-faq'),
            'all_items'             => __('All FAQs', 'elementor-faq'),
            'search_items'          => __('Search FAQs', 'elementor-faq'),
            'parent_item_colon'     => __('Parent FAQs:', 'elementor-faq'),
            'not_found'             => __('No FAQs found.', 'elementor-faq'),
            'not_found_in_trash'    => __('No FAQs found in Trash.', 'elementor-faq'),
            'featured_image'        => __('FAQ Image', 'elementor-faq'),
            'set_featured_image'    => __('Set FAQ image', 'elementor-faq'),
            'remove_featured_image' => __('Remove FAQ image', 'elementor-faq'),
            'use_featured_image'    => __('Use as FAQ image', 'elementor-faq'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-editor-help',
            'supports'            => ['title'],
            'show_in_rest'        => true,
            'exclude_from_search' => true,
            'register_meta_box_cb' => [$this, 'add_meta_boxes'],
        ];

        register_post_type('faq-item', $args);

        add_filter('manage_faq-item_posts_columns', [$this, 'set_columns']);
        add_action('manage_faq-item_posts_custom_column', [$this, 'render_columns'], 10, 2);
        add_filter('manage_edit-faq-item_sortable_columns', [$this, 'sortable_columns']);
        add_action('save_post_faq-item', [$this, 'save_faq_data']);
    }

    public function add_meta_boxes(): void
    {
        add_meta_box(
            'efaq_qa_items',
            __('Questions & Answers', 'elementor-faq'),
            [$this, 'render_qa_meta_box'],
            'faq-item',
            'normal',
            'high'
        );
    }

    public function render_qa_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('efaq_qa_items_nonce', 'efaq_qa_items_nonce');
        
        $qa_items = get_post_meta($post->ID, '_efaq_qa_items', true);
        if (!is_array($qa_items) || empty($qa_items)) {
            $qa_items = [['question' => '', 'answer' => '']];
        }
        ?>
        <div id="efaq-qa-wrapper">
            <div id="efaq-qa-items">
                <?php foreach ($qa_items as $index => $item): ?>
                <div class="efaq-qa-item" data-index="<?php echo esc_attr($index); ?>">
                    <div class="efaq-qa-header">
                        <span class="efaq-qa-number"><?php echo intval($index) + 1; ?>.</span>
                        <button type="button" class="efaq-remove-item button" <?php echo count($qa_items) === 1 ? 'style="display:none;"' : ''; ?>>
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <div class="efaq-qa-field">
                        <label><?php _e('Question', 'elementor-faq'); ?></label>
                        <textarea name="efaq_qa_items[<?php echo esc_attr($index); ?>][question]" rows="2" placeholder="<?php esc_attr_e('Enter your question here...', 'elementor-faq'); ?>"><?php echo esc_textarea($item['question'] ?? ''); ?></textarea>
                    </div>
                    <div class="efaq-qa-field">
                        <label><?php _e('Answer', 'elementor-faq'); ?></label>
                        <textarea name="efaq_qa_items[<?php echo esc_attr($index); ?>][answer]" rows="4" placeholder="<?php esc_attr_e('Enter the answer here...', 'elementor-faq'); ?>"><?php echo esc_textarea($item['answer'] ?? ''); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="efaq-add-item" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Question', 'elementor-faq'); ?>
            </button>
        </div>
        <?php
    }

    public function save_faq_data(int $post_id): void
    {
        if (!isset($_POST['efaq_qa_items_nonce']) || !wp_verify_nonce($_POST['efaq_qa_items_nonce'], 'efaq_qa_items_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $qa_items = isset($_POST['efaq_qa_items']) && is_array($_POST['efaq_qa_items']) 
            ? array_map(function($item) {
                return [
                    'question' => sanitize_textarea_field($item['question'] ?? ''),
                    'answer' => sanitize_textarea_field($item['answer'] ?? ''),
                ];
            }, $_POST['efaq_qa_items'])
            : [];

        $qa_items = array_filter($qa_items, function($item) {
            return !empty($item['question']) || !empty($item['answer']);
        });

        if (empty($qa_items)) {
            delete_post_meta($post_id, '_efaq_qa_items');
        } else {
            update_post_meta($post_id, '_efaq_qa_items', array_values($qa_items));
        }
    }

    public function set_columns(array $columns): array
    {
        $new_columns = [
            'cb'        => $columns['cb'],
            'title'     => __('Question', 'elementor-faq'),
            'category'  => __('Category', 'elementor-faq'),
            'order'     => __('Order', 'elementor-faq'),
            'date'      => __('Date', 'elementor-faq'),
        ];

        return $new_columns;
    }

    public function render_columns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'category':
                $terms = get_the_terms($post_id, 'faq-category');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = array_map(function ($term) {
                        return sprintf(
                            '<a href="%s">%s</a>',
                            esc_url(admin_url('edit.php?faq-category=' . $term->slug . '&post_type=faq-item')),
                            esc_html($term->name)
                        );
                    }, $terms);
                    echo implode(', ', $term_names);
                } else {
                    echo '—';
                }
                break;

            case 'order':
                $order = get_post_field('menu_order', $post_id);
                echo esc_html($order);
                break;
        }
    }

    public function sortable_columns(array $columns): array
    {
        $columns['order'] = 'menu_order';
        return $columns;
    }
}
