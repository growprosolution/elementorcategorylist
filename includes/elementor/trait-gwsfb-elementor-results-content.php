<?php
if (!defined('ABSPATH')) exit;

trait GWSFB_Elementor_Results_Content_Controls {

  protected function register_results_content_controls() {

    $this->start_controls_section('section_content', [
      'label' => __('Content', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
    ]);

    $sets    = class_exists('GWSFB_Helpers') ? GWSFB_Helpers::get_sets() : [];
    $options = ['' => __('Select a Filter Set', 'gwsfb')];

    if (is_array($sets)) {
      foreach ($sets as $id => $set) {
        $options[$id] = ($set['name'] ?? 'Untitled') . ' (#' . $id . ')';
      }
    }

    $this->add_control('set_id', [
      'label'   => __('Filter Set', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => $options,
      'default' => '',
    ]);

    $this->add_control('group_key', [
      'label'   => __('Group Key (must match Filters)', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '',
    ]);

    $this->add_control('show_summary', [
      'label'        => __('Show Summary (total and page)', 'gwsfb'),
      'type'         => \Elementor\Controls_Manager::SWITCHER,
      'label_on'     => __('Show', 'gwsfb'),
      'label_off'    => __('Hide', 'gwsfb'),
      'return_value' => '1',
      'default'      => '1',
    ]);

    $this->add_control('title_html_tag', [
      'label'   => __('Title HTML Tag', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'h1'   => 'H1',
        'h2'   => 'H2',
        'h3'   => 'H3',
        'h4'   => 'H4',
        'h5'   => 'H5',
        'h6'   => 'H6',
        'p'    => 'p',
        'span' => 'span',
        'div'  => 'div',
      ],
      'default' => 'h2',
    ]);

    $this->add_control('layout_modules_heading', [
      'label'     => __('Layout Modules', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep = new \Elementor\Repeater();

    $rep->add_control('layout_icon', [
      'label'   => __('Icon', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::ICONS,
      'default' => [
        'value'   => 'eicon-posts-grid',
        'library' => 'elementor-icons',
      ],
    ]);

    $rep->add_control('layout_type', [
      'label'   => __('Type', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'grid' => __('Grid', 'gwsfb'),
        'list' => __('List', 'gwsfb'),
      ],
      'default' => 'grid',
    ]);

    $rep->add_control('layout_label', [
      'label'   => __('Label (optional)', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '',
    ]);

    $rep->add_control('layout_config_heading', [
      'label'     => __('Layout', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_responsive_control('grid_columns', [
      'label'          => __('Columns (Grid)', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::NUMBER,
      'min'            => 1,
      'max'            => 6,
      'default'        => 4,
      'tablet_default' => 2,
      'mobile_default' => 1,
      'condition'      => ['layout_type' => 'grid'],
    ]);

    $rep->add_responsive_control('grid_rows', [
      'label'          => __('Rows (Grid)', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::NUMBER,
      'min'            => 0,
      'max'            => 50,
      'step'           => 1,
      'default'        => 0,
      'tablet_default' => 0,
      'mobile_default' => 0,
      'description'    => __('0 = use Filter Set per-page setting', 'gwsfb'),
      'condition'      => ['layout_type' => 'grid'],
    ]);

    $rep->add_responsive_control('list_rows', [
      'label'          => __('Rows (List)', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::NUMBER,
      'min'            => 0,
      'max'            => 50,
      'step'           => 1,
      'default'        => 0,
      'tablet_default' => 0,
      'mobile_default' => 0,
      'description'    => __('0 = use Filter Set per-page setting', 'gwsfb'),
      'condition'      => ['layout_type' => 'list'],
    ]);

    $rep->add_control('list_layout_heading', [
      'label'     => __('List Card Layout', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
      'condition' => ['layout_type' => 'list'],
    ]);

    $rep->add_responsive_control('list_mobile_layout', [
      'label'          => __('List Direction', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SELECT,
      'options'        => [
        'row'    => __('Left / Right', 'gwsfb'),
        'column' => __('Top / Bottom', 'gwsfb'),
      ],
      'default'        => 'row',
      'tablet_default' => 'row',
      'mobile_default' => 'column',
      'condition'      => ['layout_type' => 'list'],
    ]);

    $rep->add_responsive_control('list_mobile_image_width', [
      'label'          => __('Image Width (%)', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SLIDER,
      'size_units'     => ['%'],
      'range'          => [
        '%' => ['min' => 15, 'max' => 80],
      ],
      'default'        => [
        'unit' => '%',
        'size' => 28,
      ],
      'tablet_default' => [
        'unit' => '%',
        'size' => 34,
      ],
      'mobile_default' => [
        'unit' => '%',
        'size' => 40,
      ],
      'condition'      => [
        'layout_type' => 'list',
      ],
    ]);

    $rep->add_responsive_control('list_mobile_vertical_align', [
      'label'          => __('Vertical Align', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::CHOOSE,
      'options'        => [
        'flex-start' => [
          'title' => __('Top', 'gwsfb'),
          'icon'  => 'eicon-v-align-top',
        ],
        'center' => [
          'title' => __('Middle', 'gwsfb'),
          'icon'  => 'eicon-v-align-middle',
        ],
        'flex-end' => [
          'title' => __('Bottom', 'gwsfb'),
          'icon'  => 'eicon-v-align-bottom',
        ],
      ],
      'default'        => 'flex-start',
      'tablet_default' => 'flex-start',
      'mobile_default' => 'flex-start',
      'condition'      => [
        'layout_type' => 'list',
      ],
    ]);

    $rep->add_control('content_heading', [
      'label'     => __('Content', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_responsive_control('show_title', [
      'label'          => __('Show Name', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SWITCHER,
      'return_value'   => '1',
      'default'        => '1',
      'tablet_default' => '1',
      'mobile_default' => '1',
    ]);

    $rep->add_responsive_control('show_price', [
      'label'          => __('Show Price', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SWITCHER,
      'return_value'   => '1',
      'default'        => '1',
      'tablet_default' => '1',
      'mobile_default' => '1',
    ]);

    $rep->add_responsive_control('show_rating', [
      'label'          => __('Show Rating', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SWITCHER,
      'return_value'   => '1',
      'default'        => '1',
      'tablet_default' => '1',
      'mobile_default' => '1',
    ]);

    $rep->add_responsive_control('show_add_to_cart', [
      'label'          => __('Show Add to Cart', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SWITCHER,
      'return_value'   => '1',
      'default'        => '1',
      'tablet_default' => '1',
      'mobile_default' => '1',
    ]);

    $rep->add_responsive_control('show_description', [
      'label'          => __('Show Description', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SWITCHER,
      'return_value'   => '1',
      'default'        => '',
      'tablet_default' => '',
      'mobile_default' => '',
    ]);

    $rep->add_responsive_control('show_view_more', [
      'label'          => __('Show View More Link', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::SWITCHER,
      'return_value'   => '1',
      'default'        => '1',
      'tablet_default' => '1',
      'mobile_default' => '1',
    ]);

    $rep->add_control('view_more_label', [
      'label'   => __('View More Text', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => 'View more',
    ]);

    $rep->add_control('options_heading', [
      'label'     => __('Options Panel (Grid)', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('options_enable', [
      'label'        => __('Enable Options Panel', 'gwsfb'),
      'type'         => \Elementor\Controls_Manager::SWITCHER,
      'return_value' => '1',
      'default'      => '1',
      'condition'    => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('options_open', [
      'label'        => __('Options Panel Default Open', 'gwsfb'),
      'type'         => \Elementor\Controls_Manager::SWITCHER,
      'return_value' => '1',
      'default'      => '',
      'condition'    => [
        'layout_type'      => 'grid',
        'options_enable'   => '1',
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('options_label', [
      'label'     => __('Options Button Label', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::TEXT,
      'default'   => 'Options',
      'condition' => [
        'layout_type'      => 'grid',
        'options_enable'   => '1',
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('style_heading', [
      'label'     => __('Style (Per Layout)', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_control('sty_icon_heading', [
      'label' => __('Icon Button', 'gwsfb'),
      'type'  => \Elementor\Controls_Manager::HEADING,
    ]);

    $rep->add_responsive_control('sty_icon_size', [
      'label'      => __('Icon Size', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px', 'em', 'rem'],
      'range'      => [
        'px'  => ['min' => 10, 'max' => 48],
        'em'  => ['min' => 0.5, 'max' => 3],
        'rem' => ['min' => 0.5, 'max' => 3],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}} .gwsfb-results__layout-icon' =>
          'font-size: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $rep->start_controls_tabs('sty_icon_tabs');

    $rep->start_controls_tab('sty_icon_tab_normal', [
      'label' => __('Normal', 'gwsfb'),
    ]);

    $rep->add_control('sty_icon_color', [
      'label'     => __('Icon Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}} .gwsfb-results__layout-icon' =>
          'color: {{VALUE}}; fill: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_icon_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}' => 'background-color: {{VALUE}};',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'sty_icon_border',
      'selector' => '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}',
    ]);

    $rep->add_responsive_control('sty_icon_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_icon_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->end_controls_tab();

    $rep->start_controls_tab('sty_icon_tab_hover', [
      'label' => __('Hover', 'gwsfb'),
    ]);

    $rep->add_control('sty_icon_color_hover', [
      'label'     => __('Icon Color (Hover)', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}:hover .gwsfb-results__layout-icon' =>
          'color: {{VALUE}}; fill: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_icon_bg_hover', [
      'label'     => __('Background (Hover)', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_icon_border_color_hover', [
      'label'     => __('Border Color (Hover)', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}:hover' => 'border-color: {{VALUE}};',
      ],
    ]);

    $rep->end_controls_tab();

    $rep->start_controls_tab('sty_icon_tab_active', [
      'label' => __('Active', 'gwsfb'),
    ]);

    $rep->add_control('sty_icon_color_active', [
      'label'     => __('Active Icon Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}.is-active .gwsfb-results__layout-icon' =>
          'color: {{VALUE}}; fill: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_icon_bg_active', [
      'label'     => __('Active Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}.is-active' => 'background-color: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_icon_border_color_active', [
      'label'     => __('Active Border Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__layout-btn{{CURRENT_ITEM}}.is-active' => 'border-color: {{VALUE}};',
      ],
    ]);

    $rep->end_controls_tab();

    $rep->end_controls_tabs();

    $rep->add_control('sty_card_heading', [
      'label'     => __('Card', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_responsive_control('sty_grid_gap', [
      'label'      => __('Items Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => ['px' => ['min' => 0, 'max' => 80]],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsfb-results__grid' =>
          'gap: {{SIZE}}{{UNIT}} !important;',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'sty_card_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'sty_card_border',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card',
    ]);

    $rep->add_responsive_control('sty_card_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_card_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_card_gap', [
      'label'      => __('Card Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => ['px' => ['min' => 0, 'max' => 40]],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card' => 'gap: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_body_gap', [
      'label'      => __('Content Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => ['px' => ['min' => 0, 'max' => 60]],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-body' => 'gap: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_card_justify_content', [
      'label'     => __('Justify Content', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::SELECT,
      'options'   => [
        ''              => __('Default', 'gwsfb'),
        'flex-start'    => __('Start', 'gwsfb'),
        'center'        => __('Center', 'gwsfb'),
        'flex-end'      => __('End', 'gwsfb'),
        'space-between' => __('Space Between', 'gwsfb'),
        'space-around'  => __('Space Around', 'gwsfb'),
        'space-evenly'  => __('Space Evenly', 'gwsfb'),
      ],
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card' => 'justify-content: {{VALUE}};',
      ],
      'condition' => [
        'layout_type' => 'grid',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
      'name'     => 'sty_card_shadow',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card',
    ]);

    $rep->add_control('sty_card_hover_heading', [
      'label'     => __('Card Hover', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'sty_card_bg_hover',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover',
    ]);

    $rep->add_control('sty_card_border_color_hover', [
      'label'     => __('Border Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover' => 'border-color: {{VALUE}};',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
      'name'     => 'sty_card_shadow_hover',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover',
    ]);

    $rep->add_control('sty_image_heading', [
      'label'     => __('Image', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_responsive_control('sty_img_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-img img' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_img_margin', [
      'label'      => __('Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-img' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_control('sty_title_heading', [
      'label'     => __('Title', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_title_typo',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-title, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-title a',
    ]);

    $rep->add_control('sty_title_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-title, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-title a' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('title_align', [
      'label'          => __('Content Align', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::CHOOSE,
      'options'        => [
        'left' => [
          'title' => __('Left', 'gwsfb'),
          'icon'  => 'eicon-text-align-left',
        ],
        'center' => [
          'title' => __('Center', 'gwsfb'),
          'icon'  => 'eicon-text-align-center',
        ],
        'right' => [
          'title' => __('Right', 'gwsfb'),
          'icon'  => 'eicon-text-align-right',
        ],
      ],
      'default'        => 'left',
      'tablet_default' => 'left',
      'mobile_default' => 'left',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_title_typo_hover',
      'label'    => __('Hover Typography', 'gwsfb'),
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-title, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-title a',
    ]);

    $rep->add_control('sty_title_color_hover', [
      'label'     => __('Hover Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-title, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-title a' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('sty_title_margin', [
      'label'      => __('Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-title' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_control('sty_desc_heading', [
      'label'     => __('Description', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_desc_typo',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-desc',
    ]);

    $rep->add_control('sty_desc_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-desc' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('desc_align', [
      'label'          => __('Content Align', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::CHOOSE,
      'options'        => [
        'left' => [
          'title' => __('Left', 'gwsfb'),
          'icon'  => 'eicon-text-align-left',
        ],
        'center' => [
          'title' => __('Center', 'gwsfb'),
          'icon'  => 'eicon-text-align-center',
        ],
        'right' => [
          'title' => __('Right', 'gwsfb'),
          'icon'  => 'eicon-text-align-right',
        ],
      ],
      'default'        => 'left',
      'tablet_default' => 'left',
      'mobile_default' => 'left',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_desc_typo_hover',
      'label'    => __('Hover Typography', 'gwsfb'),
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-desc',
    ]);

    $rep->add_control('sty_desc_color_hover', [
      'label'     => __('Hover Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-desc' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('sty_desc_margin', [
      'label'      => __('Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-desc' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_control('sty_price_heading', [
      'label'     => __('Price', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_price_typo',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-price, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-price *',
    ]);

    $rep->add_control('sty_price_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-price, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-price *' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('price_align', [
      'label'          => __('Content Align', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::CHOOSE,
      'options'        => [
        'left' => [
          'title' => __('Left', 'gwsfb'),
          'icon'  => 'eicon-text-align-left',
        ],
        'center' => [
          'title' => __('Center', 'gwsfb'),
          'icon'  => 'eicon-text-align-center',
        ],
        'right' => [
          'title' => __('Right', 'gwsfb'),
          'icon'  => 'eicon-text-align-right',
        ],
      ],
      'default'        => 'left',
      'tablet_default' => 'left',
      'mobile_default' => 'left',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_price_typo_hover',
      'label'    => __('Hover Typography', 'gwsfb'),
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-price, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-price *',
    ]);

    $rep->add_control('sty_price_color_hover', [
      'label'     => __('Hover Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-price, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-price *' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('sty_price_margin', [
      'label'      => __('Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-price' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_control('sty_more_heading', [
      'label'     => __('View More Link', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_more_typo',
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-morelink',
    ]);

    $rep->add_control('sty_more_color', [
      'label'     => __('Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-morelink' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('more_align', [
      'label'          => __('Content Align', 'gwsfb'),
      'type'           => \Elementor\Controls_Manager::CHOOSE,
      'options'        => [
        'left' => [
          'title' => __('Left', 'gwsfb'),
          'icon'  => 'eicon-text-align-left',
        ],
        'center' => [
          'title' => __('Center', 'gwsfb'),
          'icon'  => 'eicon-text-align-center',
        ],
        'right' => [
          'title' => __('Right', 'gwsfb'),
          'icon'  => 'eicon-text-align-right',
        ],
      ],
      'default'        => 'left',
      'tablet_default' => 'left',
      'mobile_default' => 'left',
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sty_more_typo_hover',
      'label'    => __('Hover Typography', 'gwsfb'),
      'selector' => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-morelink',
    ]);

    $rep->add_control('sty_more_color_hover', [
      'label'     => __('Hover Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-morelink' => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_more_decoration_hover', [
      'label'     => __('Hover Decoration', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::SELECT,
      'options'   => [
        ''             => __('Default', 'gwsfb'),
        'none'         => 'none',
        'underline'    => 'underline',
        'line-through' => 'line-through',
        'overline'     => 'overline',
      ],
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-morelink' => 'text-decoration: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('sty_more_margin', [
      'label'      => __('Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-morelink' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_control('sty_rating_heading', [
      'label'     => __('Rating', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $rep->add_control('sty_rating_color', [
      'label'     => __('Star Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-rating .star-rating span:before' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-rating .star-rating:before'      => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_control('sty_rating_color_hover', [
      'label'     => __('Hover Star Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-rating .star-rating span:before' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-card:hover .gwsr-rating .star-rating:before'      => 'color: {{VALUE}};',
      ],
    ]);

    $rep->add_responsive_control('sty_rating_size', [
      'label'      => __('Star Size', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px', 'em', 'rem'],
      'range'      => [
        'px'  => ['min' => 8,  'max' => 40],
        'em'  => ['min' => 0.5,'max' => 3],
        'rem' => ['min' => 0.5,'max' => 3],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-rating .star-rating' => 'font-size: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $rep->add_responsive_control('sty_rating_margin', [
      'label'      => __('Margin', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-rating' =>
          'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $rep->add_control('sty_btn_heading', [
      'label'     => __('Add to Cart Button', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_btn_align', [
      'label'   => __('Align', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::CHOOSE,
      'options' => [
        'flex-start' => ['title' => 'Left',   'icon' => 'eicon-text-align-left'],
        'center'     => ['title' => 'Center', 'icon' => 'eicon-text-align-center'],
        'flex-end'   => ['title' => 'Right',  'icon' => 'eicon-text-align-right'],
        'stretch'    => ['title' => 'Stretch','icon' => 'eicon-h-align-stretch'],
      ],
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn' => 'display:flex;justify-content: {{VALUE}};',
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button' => 'width:auto;',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_btn_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'      => 'sty_btn_typo',
      'selector'  => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button',
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('sty_btn_text_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button' => 'color: {{VALUE}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('sty_btn_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button' => 'background-color: {{VALUE}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'      => 'sty_btn_border',
      'selector'  => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button',
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_btn_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('sty_btn_text_color_hover', [
      'label'     => __('Hover Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button:hover, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button:hover' => 'color: {{VALUE}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('sty_btn_bg_hover', [
      'label'     => __('Hover Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button:hover, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button:hover' => 'background-color: {{VALUE}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('sty_btn_border_color_hover', [
      'label'     => __('Hover Border Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn .button:hover, {{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-btn a.button:hover' => 'border-color: {{VALUE}};',
      ],
      'condition' => [
        'show_add_to_cart' => '1',
      ],
    ]);

    $rep->add_control('sty_opt_heading', [
      'label'     => __('Options Panel', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'      => 'sty_opt_btn_typo',
      'selector'  => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-toggle',
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_control('sty_opt_btn_color', [
      'label'     => __('Button Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-toggle' => 'color: {{VALUE}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_control('sty_opt_btn_bg', [
      'label'     => __('Button Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-toggle' => 'background-color: {{VALUE}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'      => 'sty_opt_btn_border',
      'selector'  => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-toggle',
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_opt_btn_radius', [
      'label'      => __('Button Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-toggle' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_opt_btn_padding', [
      'label'      => __('Button Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', 'em', 'rem'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-toggle' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_opt_panel_padding', [
      'label'      => __('Panel Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-options' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_control('sty_opt_panel_bg', [
      'label'     => __('Panel Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-options' => 'background-color: {{VALUE}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'      => 'sty_opt_panel_border',
      'selector'  => '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-options',
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $rep->add_responsive_control('sty_opt_panel_radius', [
      'label'      => __('Panel Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-results__wrap{{CURRENT_ITEM}} .gwsr-options' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
      'condition' => [
        'layout_type'      => 'grid',
        'show_add_to_cart' => '1',
        'options_enable'   => '1',
      ],
    ]);

    $this->add_control('layout_modules', [
      'label'       => __('Layouts', 'gwsfb'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $rep->get_controls(),
      'default'     => [
        ['layout_type' => 'grid', 'layout_label' => 'Grid'],
        ['layout_type' => 'list', 'layout_label' => 'List'],
      ],
      'title_field' => '{{{ layout_label }}}',
    ]);

    $this->add_control('loading_heading', [
      'label'     => __('Loading', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $this->add_control('loading_spinner_shape', [
      'label'   => __('Loading Spinner Shape', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'circle'  => __('Circle', 'gwsfb'),
        'square'  => __('Square', 'gwsfb'),
        'rounded' => __('Rounded Square', 'gwsfb'),
      ],
      'default' => 'circle',
    ]);

    $this->end_controls_section();
  }
}