<?php

namespace Elementor_FAQ\Taxonomies;

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Category
{
    public function register(): void
    {
        $labels = [
            'name'                       => __('FAQ Categories', 'elementor-faq'),
            'singular_name'              => __('FAQ Category', 'elementor-faq'),
            'menu_name'                  => __('Categories', 'elementor-faq'),
            'all_items'                  => __('All Categories', 'elementor-faq'),
            'parent_item'                => __('Parent Category', 'elementor-faq'),
            'parent_item_colon'          => __('Parent Category:', 'elementor-faq'),
            'new_item_name'              => __('New Category Name', 'elementor-faq'),
            'add_new_item'               => __('Add New Category', 'elementor-faq'),
            'edit_item'                  => __('Edit Category', 'elementor-faq'),
            'update_item'                => __('Update Category', 'elementor-faq'),
            'view_item'                  => __('View Category', 'elementor-faq'),
            'separate_items_with_commas' => __('Separate categories with commas', 'elementor-faq'),
            'add_or_remove_items'        => __('Add or remove categories', 'elementor-faq'),
            'choose_from_most_used'      => __('Choose from the most used', 'elementor-faq'),
            'popular_items'              => __('Popular Categories', 'elementor-faq'),
            'search_items'               => __('Search Categories', 'elementor-faq'),
            'not_found'                  => __('Not Found', 'elementor-faq'),
            'no_terms'                   => __('No categories', 'elementor-faq'),
            'items_list'                 => __('Categories list', 'elementor-faq'),
            'items_list_navigation'      => __('Categories list navigation', 'elementor-faq'),
        ];

        $args = [
            'labels'             => $labels,
            'hierarchical'       => true,
            'public'             => false,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_nav_menus'  => false,
            'show_tagcloud'      => false,
            'rewrite'            => false,
            'show_in_rest'       => true,
        ];

        register_taxonomy('faq-category', ['faq-item'], $args);
    }
}
