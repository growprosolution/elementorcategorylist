<?php
if (!defined('ABSPATH')) exit;

trait GWSFB_Elementor_Results_Style_Controls {

  protected function register_results_style_controls() {

    $this->start_controls_section('style_layout', [
      'label' => __('Layout', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_responsive_control('results_wrapper_padding', [
      'label'      => __('Wrapper Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', 'em', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__outer' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        '{{WRAPPER}} .gwsfb-headerrow' =>
          'padding-left: {{LEFT}}{{UNIT}}; padding-right: {{RIGHT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('grid_align_items', [
      'label'   => __('Align Items', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        ''           => __('Default', 'gwsfb'),
        'stretch'    => __('Stretch', 'gwsfb'),
        'flex-start' => __('Top', 'gwsfb'),
        'center'     => __('Center', 'gwsfb'),
        'flex-end'   => __('Bottom', 'gwsfb'),
      ],
      'default'   => '',
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__grid' => 'align-items: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_loading', [
      'label' => __('Loading', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_control('loading_overlay_bg', [
      'label'     => __('Overlay Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb' => '--gwsfb-loading-bg: {{VALUE}};',
      ],
    ]);

    $this->add_control('loading_spinner_color', [
      'label'     => __('Spinner Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb' => '--gwsfb-loading-fg: {{VALUE}};',
      ],
    ]);

    $this->add_control('loading_overlay_opacity', [
      'label'     => __('Overlay Opacity', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::NUMBER,
      'min'       => 0,
      'max'       => 1,
      'step'      => 0.05,
      'selectors' => [
        '{{WRAPPER}} .gwsfb' => '--gwsfb-loading-opacity: {{VALUE}};',
      ],
    ]);

    $this->add_responsive_control('loading_spinner_size', [
      'label'      => __('Spinner Size', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 10, 'max' => 120],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__spinner'      => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
        '{{WRAPPER}} .gwsfb__spinner-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('loading_spinner_thickness', [
      'label'      => __('Spinner Thickness', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 1, 'max' => 12],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__spinner' => 'border-width: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->add_control('loading_spin_speed', [
      'label'     => __('Spin Speed (s)', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::NUMBER,
      'min'       => 0.2,
      'max'       => 6,
      'step'      => 0.1,
      'default'   => 0.8,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__spinner'                   => 'animation-duration: {{VALUE}}s;',
        '{{WRAPPER}} .gwsfb__spinner-icon.is-spinning' => 'animation-duration: {{VALUE}}s;',
      ],
    ]);

    $this->add_control('loading_inner_heading', [
      'label'     => __('Inner Box', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $this->add_control('loading_inner_bg', [
      'label'     => __('Inner Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__loading-inner' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'loading_inner_border',
      'selector' => '{{WRAPPER}} .gwsfb__loading-inner',
    ]);

    $this->add_responsive_control('loading_inner_radius', [
      'label'      => __('Inner Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__loading-inner' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('loading_inner_padding', [
      'label'      => __('Inner Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__loading-inner' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_header', [
      'label' => __('Top Bar', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_responsive_control('header_spacing_bottom', [
      'label'      => __('Bottom Spacing', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 80],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-headerrow' => 'margin-bottom: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name'     => 'header_border',
        'selector' => '{{WRAPPER}} .gwsfb-headerrow',
      ]
    );

    $this->add_responsive_control('header_border_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-headerrow' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_control('header_background', [
      'label'     => __('Background Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-headerrow' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'summary_typography',
        'label'    => __('Summary Typography', 'gwsfb'),
        'selector' => '{{WRAPPER}} .gwsfb-page-summary',
      ]
    );

    $this->add_control('summary_color', [
      'label'     => __('Summary Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-page-summary' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('summary_total_color', [
      'label'     => __('Total Number Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-page-summary__total' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_layout_toggle', [
      'label' => __('Layout Toggle', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_responsive_control('layout_toggle_gap', [
      'label'      => __('Icon Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px', 'em', '%'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 40],
        'em' => ['min' => 0, 'max' => 5],
        '%'  => ['min' => 0, 'max' => 10],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__layout-toggle .gwsfb-results__layout-btn + .gwsfb-results__layout-btn' =>
          'margin-left: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_pagination', [
      'label' => __('Pagination', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_responsive_control('pagination_spacing_top', [
      'label'      => __('Top Spacing', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 80],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__pager' => 'margin-top: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('pagination_alignment', [
      'label'   => __('Alignment', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::CHOOSE,
      'options' => [
        'flex-start' => [
          'title' => __('Left', 'gwsfb'),
          'icon'  => 'eicon-text-align-left',
        ],
        'center' => [
          'title' => __('Center', 'gwsfb'),
          'icon'  => 'eicon-text-align-center',
        ],
        'flex-end' => [
          'title' => __('Right', 'gwsfb'),
          'icon'  => 'eicon-text-align-right',
        ],
      ],
      'default'   => 'flex-start',
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager' => 'display:flex; justify-content: {{VALUE}};',
      ],
    ]);

    $this->add_responsive_control('pagination_gap', [
      'label'      => __('Button Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 40],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page' => 'margin-left: {{SIZE}}{{UNIT}};',
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page:first-child' => 'margin-left: 0;',
      ],
    ]);

    $this->start_controls_tabs('pagination_tabs');

    $this->start_controls_tab('pagination_tab_normal', [
      'label' => __('Normal', 'gwsfb'),
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'pagination_typography',
        'selector' => '{{WRAPPER}} .gwsfb__pager .gwsfb__page',
      ]
    );

    $this->add_control('pagination_text_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('pagination_bg_color', [
      'label'     => __('Background Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name'     => 'pagination_border',
        'selector' => '{{WRAPPER}} .gwsfb__pager .gwsfb__page',
      ]
    );

    $this->add_responsive_control('pagination_border_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('pagination_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', 'em'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('pagination_tab_hover', [
      'label' => __('Hover', 'gwsfb'),
    ]);

    $this->add_control('pagination_text_color_hover', [
      'label'     => __('Hover Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page:hover' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('pagination_bg_color_hover', [
      'label'     => __('Hover Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('pagination_tab_active', [
      'label' => __('Active', 'gwsfb'),
    ]);

    $this->add_control('pagination_active_text_color', [
      'label'     => __('Active Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page.is-current' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('pagination_active_bg_color', [
      'label'     => __('Active Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__pager .gwsfb__page.is-current' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->end_controls_tabs();

    $this->end_controls_section();

    $this->start_controls_section('style_card', [
      'label' => __('Product Card', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_control('card_background', [
      'label'     => __('Background Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-card' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name'     => 'card_border',
        'selector' => '{{WRAPPER}} .gwsr-card',
      ]
    );

    $this->add_responsive_control('card_border_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-card' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('card_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', 'em'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-card' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Box_Shadow::get_type(),
      [
        'name'     => 'card_shadow',
        'selector' => '{{WRAPPER}} .gwsr-card',
      ]
    );

    $this->end_controls_section();

    $this->start_controls_section('style_image', [
      'label' => __('Product Image', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'img_wrap_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsr-img',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'img_wrap_border',
      'selector' => '{{WRAPPER}} .gwsr-img',
    ]);

    $this->add_responsive_control('img_wrap_radius', [
      'label'      => __('Container Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('img_wrap_padding', [
      'label'      => __('Container Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('img_wrap_margin', [
      'label'      => __('Container Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_control('img_real_heading', [
      'label'     => __('Actual Image', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'img_real_border',
      'selector' => '{{WRAPPER}} .gwsr-img img.gwsr-product-img',
    ]);

    $this->add_responsive_control('img_real_radius', [
      'label'      => __('Image Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img img.gwsr-product-img' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('img_real_padding', [
      'label'      => __('Image Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img img.gwsr-product-img' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('img_real_margin', [
      'label'      => __('Image Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img img.gwsr-product-img' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_image_placeholder', [
      'label' => __('Image Placeholder', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'ph_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsr-img-ph',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'ph_border',
      'selector' => '{{WRAPPER}} .gwsr-img-ph',
    ]);

    $this->add_responsive_control('ph_radius', [
      'label'      => __('Placeholder Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img-ph' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('ph_padding', [
      'label'      => __('Placeholder Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img-ph' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('ph_margin', [
      'label'      => __('Placeholder Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img-ph' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_control('ph_text_heading', [
      'label'     => __('Placeholder Text', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'ph_text_typo',
      'selector' => '{{WRAPPER}} .gwsr-img-ph-text',
    ]);

    $this->add_control('ph_text_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-img-ph-text' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_responsive_control('ph_text_padding', [
      'label'      => __('Text Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img-ph-text' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_control('ph_image_heading', [
      'label'     => __('Placeholder Image', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $this->add_responsive_control('ph_image_radius', [
      'label'      => __('Placeholder Image Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsr-img-ph-image' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_title', [
      'label' => __('Product Title', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'title_typography',
        'selector' => '{{WRAPPER}} .gwsr-title, {{WRAPPER}} .gwsr-title a',
      ]
    );

    $this->add_control('title_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-title, {{WRAPPER}} .gwsr-title a' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('title_color_hover', [
      'label'     => __('Hover Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-card:hover .gwsr-title, {{WRAPPER}} .gwsr-card:hover .gwsr-title a' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_price', [
      'label' => __('Price', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'price_typography',
        'selector' => '{{WRAPPER}} .gwsr-price, {{WRAPPER}} .gwsr-price *',
      ]
    );

    $this->add_control('price_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-price, {{WRAPPER}} .gwsr-price *' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_rating', [
      'label' => __('Rating', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'rating_typography',
        'selector' => '{{WRAPPER}} .gwsr-rating, {{WRAPPER}} .gwsr-rating .star-rating',
      ]
    );

    $this->add_control('rating_color', [
      'label'     => __('Star Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-rating .star-rating span:before' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_desc', [
      'label' => __('Description', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'desc_typography',
        'selector' => '{{WRAPPER}} .gwsr-desc',
      ]
    );

    $this->add_control('desc_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-desc' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_more_link', [
      'label' => __('"View More" Link', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'more_link_typography',
        'selector' => '{{WRAPPER}} .gwsr-morelink',
      ]
    );

    $this->add_control('more_link_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-morelink' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('more_link_color_hover', [
      'label'     => __('Hover Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-card:hover .gwsr-morelink' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_buttons', [
      'label' => __('Buttons', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->start_controls_tabs('buttons_tabs');

    $this->start_controls_tab('button_tab_primary', [
      'label' => __('Add to Cart', 'gwsfb'),
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'primary_button_typography',
        'selector' => '{{WRAPPER}} .gwsr-btn .button',
      ]
    );

    $this->add_control('primary_button_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-btn .button' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('primary_button_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-btn .button' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('primary_button_color_hover', [
      'label'     => __('Hover Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-btn .button:hover' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('primary_button_bg_hover', [
      'label'     => __('Hover Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-btn .button:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('button_tab_options', [
      'label' => __('Options Toggle', 'gwsfb'),
    ]);

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name'     => 'options_button_typography',
        'selector' => '{{WRAPPER}} .gwsr-toggle',
      ]
    );

    $this->add_control('options_button_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-toggle' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('options_button_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-toggle' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('options_button_color_hover', [
      'label'     => __('Hover Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-toggle:hover' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('options_button_bg_hover', [
      'label'     => __('Hover Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsr-toggle:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->end_controls_tabs();
    $this->end_controls_section();
  }
}