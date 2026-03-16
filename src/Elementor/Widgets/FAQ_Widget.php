<?php

namespace Elementor_FAQ\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Core\Breakpoints\Manager as Breakpoints_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Widget extends Widget_Base
{
    public function get_name(): string
    {
        return 'elementor_faq';
    }

    public function get_title(): string
    {
        return __('FAQ', 'elementor-faq');
    }

    public function get_icon(): string
    {
        return 'eicon-toggle';
    }

    public function get_categories(): array
    {
        return ['yosh-tools'];
    }

    public function get_keywords(): array
    {
        return ['faq', 'accordion', 'questions', 'help', 'yosh', 'tools'];
    }

    public function get_style_depends(): array
    {
        return ['elementor-faq-widget'];
    }

    public function get_script_depends(): array
    {
        return ['elementor-faq-widget'];
    }

    protected function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_behavior_controls();
        $this->register_question_style_controls();
        $this->register_answer_style_controls();
        $this->register_container_style_controls();
        $this->register_category_style_controls();
        $this->register_search_style_controls();
    }

    private function register_content_controls(): void
    {
        $this->start_controls_section('content_section', [
            'label' => __('FAQ Source', 'elementor-faq'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('source', [
            'label'   => __('Source', 'elementor-faq'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'all',
            'options' => [
                'all'      => __('All FAQs', 'elementor-faq'),
                'category' => __('By Category', 'elementor-faq'),
                'specific' => __('Specific FAQs', 'elementor-faq'),
            ],
        ]);

        $categories = $this->get_faq_categories();

        $this->add_control('faq_category', [
            'label'     => __('Select Category', 'elementor-faq'),
            'type'      => Controls_Manager::SELECT2,
            'options'   => $categories,
            'default'   => '',
            'multiple'  => true,
            'condition' => ['source' => 'category'],
        ]);

        $this->add_control('faq_ids', [
            'label'     => __('Select FAQs', 'elementor-faq'),
            'type'      => Controls_Manager::SELECT2,
            'options'   => $this->get_faqs(),
            'default'   => [],
            'multiple'  => true,
            'condition' => ['source' => 'specific'],
        ]);

        $this->add_control('orderby', [
            'label'   => __('Order By', 'elementor-faq'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'menu_order',
            'options' => [
                'menu_order' => __('Menu Order', 'elementor-faq'),
                'date'       => __('Date', 'elementor-faq'),
                'title'      => __('Title (A-Z)', 'elementor-faq'),
                'rand'       => __('Random', 'elementor-faq'),
            ],
        ]);

        $this->add_control('order', [
            'label'   => __('Order', 'elementor-faq'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'ASC',
            'options' => [
                'ASC'  => __('Ascending', 'elementor-faq'),
                'DESC' => __('Descending', 'elementor-faq'),
            ],
        ]);

        $this->add_control('limit', [
            'label'   => __('Limit', 'elementor-faq'),
            'type'    => Controls_Manager::NUMBER,
            'default' => -1,
            'min'     => -1,
            'step'    => 1,
        ]);

        $this->end_controls_section();
    }

    private function register_behavior_controls(): void
    {
        $this->start_controls_section('behavior_section', [
            'label' => __('Behavior', 'elementor-faq'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('toggle_mode', [
            'label'   => __('Toggle Mode', 'elementor-faq'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'single',
            'options' => [
                'single'   => __('Single (Close others)', 'elementor-faq'),
                'multiple' => __('Multiple (Independent)', 'elementor-faq'),
            ],
        ]);

        $this->add_control('enable_search', [
            'label'        => __('Enable Search', 'elementor-faq'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'elementor-faq'),
            'label_off'    => __('No', 'elementor-faq'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('search_placeholder', [
            'label'     => __('Search Placeholder', 'elementor-faq'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Search FAQs...', 'elementor-faq'),
            'condition' => ['enable_search' => 'yes'],
        ]);

        $this->add_control('enable_categories', [
            'label'        => __('Enable Category Filter', 'elementor-faq'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'elementor-faq'),
            'label_off'    => __('No', 'elementor-faq'),
            'return_value' => 'yes',
            'default'      => 'no',
            'condition'    => ['source!' => 'specific'],
        ]);

        $this->add_control('category_layout', [
            'label'     => __('Category Layout', 'elementor-faq'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'horizontal',
            'options'   => [
                'horizontal' => __('Horizontal Tabs', 'elementor-faq'),
                'vertical'   => __('Vertical Tabs', 'elementor-faq'),
            ],
            'condition' => [
                'enable_categories' => 'yes',
                'source!'           => 'specific',
            ],
        ]);

        $this->add_control('default_expand', [
            'label'   => __('Default Expand', 'elementor-faq'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none'  => __('None', 'elementor-faq'),
                'first' => __('First FAQ', 'elementor-faq'),
                'all'   => __('All FAQs', 'elementor-faq'),
            ],
        ]);

        $this->add_control('animation_speed', [
            'label'   => __('Animation Speed (ms)', 'elementor-faq'),
            'type'    => Controls_Manager::SLIDER,
            'default' => ['size' => 300],
            'range'   => [
                'px' => [
                    'min'  => 0,
                    'max'  => 1000,
                    'step' => 50,
                ],
            ],
        ]);

        $this->add_control('icon_open', [
            'label'   => __('Open Icon', 'elementor-faq'),
            'type'    => Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-plus',
                'library' => 'fa-solid',
            ],
        ]);

        $this->add_control('icon_close', [
            'label'   => __('Close Icon', 'elementor-faq'),
            'type'    => Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-minus',
                'library' => 'fa-solid',
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('responsive_section', [
            'label' => __('Responsive', 'elementor-faq'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('tablet_breakpoint', [
            'label'   => __('Tablet Breakpoint (px)', 'elementor-faq'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 1024,
            'min'     => 480,
            'max'     => 1200,
        ]);

        $this->add_control('mobile_breakpoint', [
            'label'   => __('Mobile Breakpoint (px)', 'elementor-faq'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 767,
            'min'     => 320,
            'max'     => 768,
        ]);

        $this->add_control('mobile_category_dropdown', [
            'label'        => __('Use Dropdown on Mobile', 'elementor-faq'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'elementor-faq'),
            'label_off'    => __('No', 'elementor-faq'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['enable_categories' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    private function register_question_style_controls(): void
    {
        $this->start_controls_section('question_style_section', [
            'label' => __('Question', 'elementor-faq'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'question_typography',
            'label'    => __('Typography', 'elementor-faq'),
            'selector' => '{{WRAPPER}} .efaq-item-question',
        ]);

        $this->start_controls_tabs('question_style_tabs');

        $this->start_controls_tab('question_normal_tab', ['label' => __('Normal', 'elementor-faq')]);

        $this->add_control('question_color', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-question' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('question_bg_color', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-question' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('question_hover_tab', ['label' => __('Hover', 'elementor-faq')]);

        $this->add_control('question_color_hover', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-question:hover' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('question_bg_color_hover', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-question:hover' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('question_active_tab', ['label' => __('Active', 'elementor-faq')]);

        $this->add_control('question_color_active', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item.active .efaq-item-question' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('question_bg_color_active', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item.active .efaq-item-question' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control('question_padding', [
            'label'      => __('Padding', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-question' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'question_border',
            'selector' => '{{WRAPPER}} .efaq-item-question',
        ]);

        $this->add_control('question_border_radius', [
            'label'      => __('Border Radius', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-question' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('icon_heading', [
            'label'     => __('Icon', 'elementor-faq'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Icon Size', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => ['min' => 10, 'max' => 50],
            ],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-icon' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->start_controls_tabs('icon_style_tabs');

        $this->start_controls_tab('icon_normal_tab', ['label' => __('Normal', 'elementor-faq')]);

        $this->add_control('icon_color', [
            'label'     => __('Icon Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-icon' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('icon_active_tab', ['label' => __('Active', 'elementor-faq')]);

        $this->add_control('icon_color_active', [
            'label'     => __('Icon Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item.active .efaq-item-icon' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control('icon_spacing', [
            'label'      => __('Icon Spacing', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    private function register_answer_style_controls(): void
    {
        $this->start_controls_section('answer_style_section', [
            'label' => __('Answer', 'elementor-faq'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'answer_typography',
            'label'    => __('Typography', 'elementor-faq'),
            'selector' => '{{WRAPPER}} .efaq-item-answer, {{WRAPPER}} .efaq-item-answer-content p',
        ]);

        $this->add_responsive_control('answer_font_size', [
            'label'      => __('Font Size', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => [
                'px'  => ['min' => 10, 'max' => 40],
                'em'  => ['min' => 0.5, 'max' => 3],
                'rem' => ['min' => 0.5, 'max' => 3],
            ],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-answer' => '--efaq-item-answer-font-size: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .efaq-item-answer-content p' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('answer_color', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-answer' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('answer_bg_color', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-item-answer' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('answer_padding', [
            'label'      => __('Padding', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-answer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'answer_border',
            'selector' => '{{WRAPPER}} .efaq-item-answer',
        ]);

        $this->add_control('answer_border_radius', [
            'label'      => __('Border Radius', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item-answer' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    private function register_container_style_controls(): void
    {
        $this->start_controls_section('container_style_section', [
            'label' => __('Container', 'elementor-faq'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('item_spacing', [
            'label'      => __('Item Spacing', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => ['min' => 0, 'max' => 50],
            ],
            'selectors'  => [
                '{{WRAPPER}} .efaq-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('container_max_width', [
            'label'      => __('Max Width', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => ['min' => 200, 'max' => 1200],
            ],
            'selectors'  => [
                '{{WRAPPER}} .efaq-wrapper' => 'max-width: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'container_box_shadow',
            'selector' => '{{WRAPPER}} .efaq-wrapper',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'container_border',
            'selector' => '{{WRAPPER}} .efaq-wrapper',
        ]);

        $this->add_control('container_border_radius', [
            'label'      => __('Border Radius', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('container_padding', [
            'label'      => __('Container Padding', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    private function register_category_style_controls(): void
    {
        $this->start_controls_section('category_style_section', [
            'label'     => __('Category Tabs', 'elementor-faq'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['enable_categories' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'category_typography',
            'label'    => __('Typography', 'elementor-faq'),
            'selector' => '{{WRAPPER}} .efaq-category-tab',
        ]);

        $this->start_controls_tabs('category_style_tabs');

        $this->start_controls_tab('category_normal_tab', ['label' => __('Normal', 'elementor-faq')]);

        $this->add_control('category_color', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-category-tab' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('category_bg_color', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-category-tab' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('category_active_tab', ['label' => __('Active', 'elementor-faq')]);

        $this->add_control('category_color_active', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-category-tab.active' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('category_bg_color_active', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-category-tab.active' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control('category_padding', [
            'label'      => __('Padding', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-category-tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('category_spacing', [
            'label'      => __('Tab Spacing', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => ['min' => 0, 'max' => 30],
            ],
            'selectors'  => [
                '{{WRAPPER}} .efaq-categories-horizontal .efaq-category-tab' => 'margin-right: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .efaq-categories-vertical .efaq-category-tab'   => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('category_border_radius', [
            'label'      => __('Border Radius', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-category-tab' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    private function register_search_style_controls(): void
    {
        $this->start_controls_section('search_style_section', [
            'label'     => __('Search', 'elementor-faq'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['enable_search' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'search_typography',
            'label'    => __('Typography', 'elementor-faq'),
            'selector' => '{{WRAPPER}} .efaq-search-input',
        ]);

        $this->add_control('search_color', [
            'label'     => __('Text Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-search-input' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('search_bg_color', [
            'label'     => __('Background Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-search-input' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('search_placeholder_color', [
            'label'     => __('Placeholder Color', 'elementor-faq'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .efaq-search-input::placeholder' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('search_padding', [
            'label'      => __('Padding', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-search-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('search_margin_bottom', [
            'label'      => __('Margin Bottom', 'elementor-faq'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => ['min' => 0, 'max' => 50],
            ],
            'selectors'  => [
                '{{WRAPPER}} .efaq-search-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'search_border',
            'selector' => '{{WRAPPER}} .efaq-search-input',
        ]);

        $this->add_control('search_border_radius', [
            'label'      => __('Border Radius', 'elementor-faq'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .efaq-search-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    private function get_faq_categories(): array
    {
        $categories = get_terms([
            'taxonomy'   => 'faq-category',
            'hide_empty' => true,
        ]);

        if (is_wp_error($categories) || empty($categories)) {
            return [];
        }

        $options = ['' => __('All Categories', 'elementor-faq')];
        foreach ($categories as $category) {
            $options[$category->term_id] = $category->name;
        }

        return $options;
    }

    private function get_faqs(): array
    {
        $faqs = get_posts([
            'post_type'      => 'faq-item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($faqs)) {
            return [];
        }

        $options = [];
        foreach ($faqs as $faq) {
            $options[$faq->ID] = $faq->post_title;
        }

        return $options;
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $args = [
            'post_type'      => 'faq-item',
            'posts_per_page' => $settings['limit'] > 0 ? $settings['limit'] : -1,
            'orderby'        => $settings['orderby'],
            'order'          => $settings['order'],
            'post_status'    => 'publish',
        ];

        if ($settings['source'] === 'category' && !empty($settings['faq_category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'faq-category',
                    'field'    => 'term_id',
                    'terms'    => $settings['faq_category'],
                ],
            ];
        }

        if ($settings['source'] === 'specific' && !empty($settings['faq_ids'])) {
            $args['post__in']       = $settings['faq_ids'];
            $args['posts_per_page'] = count($settings['faq_ids']);
        }

        $faqs = new \WP_Query($args);

        if (!$faqs->have_posts()) {
            echo '<p>' . esc_html__('No FAQs found.', 'elementor-faq') . '</p>';
            return;
        }

        $wrapper_classes = ['efaq-wrapper'];
        $wrapper_classes[] = 'efaq-animation-speed-' . intval($settings['animation_speed']['size']);

        if ($settings['enable_categories'] === 'yes') {
            $categories = $this->get_faq_categories_for_display($faqs);
        }

        $this->add_render_attribute('wrapper', 'class', $wrapper_classes);
        $this->add_render_attribute('wrapper', 'data-toggle-mode', $settings['toggle_mode']);
        $this->add_render_attribute('wrapper', 'data-default-expand', $settings['default_expand']);
        $this->add_render_attribute('wrapper', 'data-animation-speed', intval($settings['animation_speed']['size']));

        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <?php if ($settings['enable_search'] === 'yes') : ?>
                <div class="efaq-search-wrapper">
                    <input type="text" class="efaq-search-input" placeholder="<?php echo esc_attr($settings['search_placeholder']); ?>">
                </div>
            <?php endif; ?>

            <?php if ($settings['enable_categories'] === 'yes' && !empty($categories)) : ?>
                <div class="efaq-categories efaq-categories-<?php echo esc_attr($settings['category_layout']); ?>">
                    <button type="button" class="efaq-category-tab active" data-category="all">
                        <?php esc_html_e('All', 'elementor-faq'); ?>
                    </button>
                    <?php foreach ($categories as $term_id => $name) : ?>
                        <button type="button" class="efaq-category-tab" data-category="<?php echo esc_attr($term_id); ?>">
                            <?php echo esc_html($name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php if ($settings['mobile_category_dropdown'] === 'yes') : ?>
                    <div class="efaq-categories-dropdown efaq-mobile-only">
                        <select class="efaq-category-select">
                            <option value="all"><?php esc_html_e('All Categories', 'elementor-faq'); ?></option>
                            <?php foreach ($categories as $term_id => $name) : ?>
                                <option value="<?php echo esc_attr($term_id); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="efaq-items">
                <?php
                $index = 0;
                while ($faqs->have_posts()) : $faqs->the_post();
                    $faq_id       = get_the_ID();
                    $faq_terms    = get_the_terms($faq_id, 'faq-category');
                    $term_classes = '';

                    if ($faq_terms && !is_wp_error($faq_terms)) {
                        $term_ids = array_map('intval', wp_list_pluck($faq_terms, 'term_id'));
                        $term_classes = implode(' ', array_map(function($id) {
                            return 'efaq-category-' . $id;
                        }, $term_ids));
                    }

                    $qa_items = get_post_meta($faq_id, '_efaq_qa_items', true);
                    
                    if (!empty($qa_items) && is_array($qa_items)) {
                        foreach ($qa_items as $qa_item) {
                            if (empty($qa_item['question']) && empty($qa_item['answer'])) {
                                continue;
                            }
                            
                            $is_active = ($settings['default_expand'] === 'first' && $index === 0) || $settings['default_expand'] === 'all';
                            ?>
                            <div class="efaq-item <?php echo $is_active ? 'active' : ''; ?> <?php echo esc_attr($term_classes); ?>" data-index="<?php echo $index; ?>">
                                <div class="efaq-item-question">
                                    <span class="efaq-item-title"><?php echo esc_html($qa_item['question']); ?></span>
                                    <span class="efaq-item-icon efaq-icon-open">
                                        <?php \Elementor\Icons_Manager::render_icon($settings['icon_open'], ['aria-hidden' => 'true']); ?>
                                    </span>
                                    <span class="efaq-item-icon efaq-icon-close">
                                        <?php \Elementor\Icons_Manager::render_icon($settings['icon_close'], ['aria-hidden' => 'true']); ?>
                                    </span>
                                </div>
                                <div class="efaq-item-answer">
                                    <div class="efaq-item-answer-content">
                                        <?php echo wpautop(esc_html($qa_item['answer'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $index++;
                        }
                    } else {
                        $is_active = ($settings['default_expand'] === 'first' && $index === 0) || $settings['default_expand'] === 'all';
                        ?>
                        <div class="efaq-item <?php echo $is_active ? 'active' : ''; ?> <?php echo esc_attr($term_classes); ?>" data-index="<?php echo $index; ?>">
                            <div class="efaq-item-question">
                                <span class="efaq-item-title"><?php the_title(); ?></span>
                                <span class="efaq-item-icon efaq-icon-open">
                                    <?php \Elementor\Icons_Manager::render_icon($settings['icon_open'], ['aria-hidden' => 'true']); ?>
                                </span>
                                <span class="efaq-item-icon efaq-icon-close">
                                    <?php \Elementor\Icons_Manager::render_icon($settings['icon_close'], ['aria-hidden' => 'true']); ?>
                                </span>
                            </div>
                            <div class="efaq-item-answer">
                                <div class="efaq-item-answer-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $index++;
                    }
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
        <?php
    }

    private function get_faq_categories_for_display(\WP_Query $faqs): array
    {
        $categories = [];

        while ($faqs->have_posts()) {
            $faqs->the_post();
            $terms = get_the_terms(get_the_ID(), 'faq-category');

            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[$term->term_id] = $term->name;
                }
            }
        }

        $faqs->rewind_posts();

        return $categories;
    }
}
