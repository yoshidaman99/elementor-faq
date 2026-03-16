<?php

namespace Elementor_FAQ\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static ?self $instance = null;

    private array $services = [];

    private function __construct()
    {
        $this->register_core_services();
        $this->init_hooks();
    }

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function register_core_services(): void
    {
        $this->services['logger'] = new Debug_Logger();
        $this->services['post_type'] = new \Elementor_FAQ\PostTypes\FAQ_Post_Type();
        $this->services['taxonomy'] = new \Elementor_FAQ\Taxonomies\FAQ_Category();
    }

    private function init_hooks(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_post_types'], 0);
        add_action('init', [$this, 'register_taxonomies'], 0);

        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }

        add_action('elementor/widgets/register', [$this, 'register_elementor_integration']);
        add_action('elementor/elements/categories_registered', [$this, 'register_elementor_category']);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'elementor-faq',
            false,
            dirname(ELEMENTOR_FAQ_BASENAME) . '/languages'
        );
    }

    public function register_post_types(): void
    {
        if (isset($this->services['post_type'])) {
            $this->services['post_type']->register();
        }
    }

    public function register_taxonomies(): void
    {
        if (isset($this->services['taxonomy'])) {
            $this->services['taxonomy']->register();
        }
    }

    public function register_elementor_category($elements_manager): void
    {
        $elements_manager->add_category('yosh-tools', [
            'title' => __('Yosh Tools', 'elementor-faq'),
            'icon'  => 'fa fa-plug',
        ]);
    }

    public function register_elementor_integration($widgets_manager): void
    {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        $integration = new \Elementor_FAQ\Elementor\Elementor_Integration();
        $integration->register_widgets($widgets_manager);
    }

    public function enqueue_admin_assets(string $hook): void
    {
        $screen = get_current_screen();

        if ($screen && in_array($screen->post_type, ['faq-item'], true)) {
            wp_enqueue_style(
                'elementor-faq-admin',
                ELEMENTOR_FAQ_URL . 'assets/css/admin.css',
                [],
                ELEMENTOR_FAQ_VERSION
            );

            wp_enqueue_script(
                'elementor-faq-admin',
                ELEMENTOR_FAQ_URL . 'assets/js/admin.js',
                ['jquery'],
                ELEMENTOR_FAQ_VERSION,
                true
            );
        }
    }

    public function get_service(string $name): ?object
    {
        return $this->services[$name] ?? null;
    }
}
