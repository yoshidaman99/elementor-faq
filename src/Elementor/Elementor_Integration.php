<?php

namespace Elementor_FAQ\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class Elementor_Integration
{
    public function register_widgets($widgets_manager): void
    {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        require_once ELEMENTOR_FAQ_DIR . 'src/Elementor/Widgets/FAQ_Widget.php';

        $widgets_manager->register(new Widgets\FAQ_Widget());
    }

    public static function register_styles(): void
    {
        wp_register_style(
            'elementor-faq-widget',
            ELEMENTOR_FAQ_URL . 'assets/css/faq.css',
            [],
            ELEMENTOR_FAQ_VERSION
        );
    }

    public static function register_scripts(): void
    {
        wp_register_script(
            'elementor-faq-widget',
            ELEMENTOR_FAQ_URL . 'assets/js/faq.js',
            ['jquery'],
            ELEMENTOR_FAQ_VERSION,
            true
        );

        wp_localize_script('elementor-faq-widget', 'elementorFAQ', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('elementor_faq_nonce'),
            'i18n'    => [
                'noResults' => __('No FAQs found matching your search.', 'elementor-faq'),
            ],
        ]);
    }
}
