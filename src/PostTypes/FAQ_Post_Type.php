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
            'supports'            => ['title', 'editor', 'thumbnail', 'page-attributes'],
            'show_in_rest'        => true,
            'exclude_from_search' => true,
        ];

        register_post_type('faq-item', $args);

        add_filter('manage_faq-item_posts_columns', [$this, 'set_columns']);
        add_action('manage_faq-item_posts_custom_column', [$this, 'render_columns'], 10, 2);
        add_filter('manage_edit-faq-item_sortable_columns', [$this, 'sortable_columns']);
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
