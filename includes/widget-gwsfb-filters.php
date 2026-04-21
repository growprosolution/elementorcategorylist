<?php
if (!defined('ABSPATH')) exit;

class GWSFB_Elementor_Widget_Filters extends \Elementor\Widget_Base {

  public function get_name() {
    return 'gwsfb_filters_panel';
  }

  public function get_title() {
    return 'GWS Filters Panel';
  }

  public function get_icon() {
    return 'eicon-filter';
  }

  public function get_categories() {
    return ['general'];
  }

  public function get_keywords() {
    return ['woo', 'filter', 'gws', 'panel'];
  }

  protected function register_controls() {

    $this->start_controls_section('section_content', [
      'label' => __('Content', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
    ]);

    $sets    = GWSFB_Helpers::get_sets();
    $options = ['' => __('Select a Filter Set', 'gwsfb')];

    foreach ($sets as $id => $set) {
      $options[$id] = ($set['name'] ?? 'Untitled') . ' (#' . $id . ')';
    }

    $this->add_control('set_id', [
      'label'   => __('Filter Set', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => $options,
      'default' => '',
    ]);

    $this->add_control('group_key', [
      'label'       => __('Group Key (link to results)', 'gwsfb'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => '',
      'description' => __('Use the same key in the Results widget to sync.', 'gwsfb'),
    ]);

    $this->add_responsive_control('attr_visibility', [
      'label'   => __('Hide options from attributes', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'show_all'   => __('Show all', 'gwsfb'),
        'hide_first' => __('Hide & show first one', 'gwsfb'),
        'hide_all'   => __('Hide all', 'gwsfb'),
      ],
      'default' => 'show_all',
    ]);

    $this->add_control('toggle_icons_heading', [
      'label'     => __('Toggle Icons', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ]);

    $this->add_control('toggle_icon_source', [
      'label'   => __('Icon Source', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'preset' => __('Preset', 'gwsfb'),
        'custom' => __('Custom Icons', 'gwsfb'),
      ],
      'default' => 'preset',
    ]);

    $this->add_control('toggle_icon_preset', [
      'label'     => __('Toggle Icon Preset', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::SELECT,
      'options'   => [
        'plusminus' => __('Plus / Minus', 'gwsfb'),
        'chevron'   => __('Chevron', 'gwsfb'),
        'caret'     => __('Caret', 'gwsfb'),
        'arrow'     => __('Arrow', 'gwsfb'),
      ],
      'default'   => 'plusminus',
      'condition' => [
        'toggle_icon_source' => 'preset',
      ],
    ]);

    $this->add_control('toggle_icon_collapsed', [
      'label'     => __('Collapsed Icon', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::ICONS,
      'default'   => [],
      'condition' => [
        'toggle_icon_source' => 'custom',
      ],
    ]);

    $this->add_control('toggle_icon_expanded', [
      'label'     => __('Expanded Icon', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::ICONS,
      'default'   => [],
      'condition' => [
        'toggle_icon_source' => 'custom',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_panel', [
      'label' => __('Panel', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'panel_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsfb-filters__panel',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'panel_border',
      'selector' => '{{WRAPPER}} .gwsfb-filters__panel',
    ]);

    $this->add_responsive_control('panel_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-filters__panel' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('panel_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-filters__panel' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('panel_gap', [
      'label'      => __('Blocks Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 40],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-filters__panel' => 'row-gap: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_block', [
      'label' => __('Filter Block', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'block_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsfb__block',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'block_border',
      'selector' => '{{WRAPPER}} .gwsfb__block',
    ]);

    $this->add_responsive_control('block_radius', [
      'label'      => __('Block Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__block' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_header', [
      'label' => __('Block Header', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'header_typography',
      'selector' => '{{WRAPPER}} .gwsfb__title-text',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'header_border',
      'selector' => '{{WRAPPER}} .gwsfb__titlebar',
    ]);

    $this->add_responsive_control('header_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__titlebar' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('header_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__titlebar' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('header_icon_size', [
      'label'      => __('Icon Size', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px', 'em', 'rem'],
      'range'      => [
        'px'  => ['min' => 8, 'max' => 48],
        'em'  => ['min' => 0.5, 'max' => 3],
        'rem' => ['min' => 0.5, 'max' => 3],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb-filter-shell' => '--gwsfb-toggle-icon-size: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->start_controls_tabs('tabs_header_states');

    $this->start_controls_tab('tab_header_collapsed', [
      'label' => __('Collapsed', 'gwsfb'),
    ]);

    $this->add_control('header_bg_collapsed', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__block.is-collapsed .gwsfb__titlebar' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('header_text_color_collapsed', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__block.is-collapsed .gwsfb__title-text' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('header_icon_color_collapsed', [
      'label'     => __('Icon Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__block.is-collapsed .gwsfb__toggle-icon' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb__block.is-collapsed .gwsfb__title-icon' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('tab_header_expanded', [
      'label' => __('Expanded', 'gwsfb'),
    ]);

    $this->add_control('header_bg_expanded', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__block.is-open .gwsfb__titlebar' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('header_text_color_expanded', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__block.is-open .gwsfb__title-text' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('header_icon_color_expanded', [
      'label'     => __('Icon Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__block.is-open .gwsfb__toggle-icon' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb__block.is-open .gwsfb__title-icon' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('tab_header_hover', [
      'label' => __('Hover', 'gwsfb'),
    ]);

    $this->add_control('header_bg_hover', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__titlebar:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('header_text_color_hover', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__titlebar:hover .gwsfb__title-text' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('header_icon_color_hover', [
      'label'     => __('Icon Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__titlebar:hover .gwsfb__toggle-icon' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb__titlebar:hover .gwsfb__title-icon' => 'color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();

    $this->end_controls_tabs();
    $this->end_controls_section();

    $this->start_controls_section('style_body', [
      'label' => __('Options Section', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'body_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsfb__body',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'body_border',
      'selector' => '{{WRAPPER}} .gwsfb__body',
    ]);

    $this->add_responsive_control('body_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__body' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('body_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__body' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'opt_typo',
      'selector' => '{{WRAPPER}} .gwsfb__opt, {{WRAPPER}} .gwsfb__opt-text, {{WRAPPER}} .gwsfb__empty',
    ]);

    $this->add_control('opt_color', [
      'label'     => __('Option Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__opt' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb__opt-text' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb__empty' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_responsive_control('block_gap', [
      'label'      => __('Block Spacing', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 30],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__block' => 'margin-bottom: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_inputs', [
      'label' => __('Inputs & Selects', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'input_typo',
      'selector' => '{{WRAPPER}} .gwsfb__input, {{WRAPPER}} .gwsfb__select, {{WRAPPER}} .gwsfb-price__value, {{WRAPPER}} .gwsfb__price-label',
    ]);

    $this->add_control('input_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__input, {{WRAPPER}} .gwsfb__select' => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb-price__value, {{WRAPPER}} .gwsfb__price-label' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('input_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__input, {{WRAPPER}} .gwsfb__select' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'input_border',
      'selector' => '{{WRAPPER}} .gwsfb__input, {{WRAPPER}} .gwsfb__select',
    ]);

    $this->add_responsive_control('input_radius', [
      'label'      => __('Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__input, {{WRAPPER}} .gwsfb__select' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('input_padding', [
      'label'      => __('Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__input, {{WRAPPER}} .gwsfb__select' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_control('price_track_color', [
      'label'     => __('Price Track Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb' => '--gwsfb-price-track-bg: {{VALUE}};',
      ],
    ]);

    $this->add_control('price_fill_color', [
      'label'     => __('Price Active Track Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb' => '--gwsfb-price-fill-bg: {{VALUE}};',
      ],
    ]);

    $this->add_control('price_thumb_color', [
      'label'     => __('Price Thumb Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb' => '--gwsfb-price-thumb-bg: {{VALUE}};',
      ],
    ]);

    $this->end_controls_section();

    $this->start_controls_section('style_loading', [
      'label' => __('Loading', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_control('loading_spinner_shape', [
      'label'   => __('Loading Animation', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'circle'  => __('Circle', 'gwsfb'),
        'square'  => __('Square', 'gwsfb'),
        'rounded' => __('Rounded Square', 'gwsfb'),
      ],
      'default' => 'circle',
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

    $this->end_controls_section();

    $this->start_controls_section('style_buttons', [
      'label' => __('Buttons', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->start_controls_tabs('tabs_btn_apply');

    $this->start_controls_tab('btn_apply_normal', [
      'label' => __('Apply Normal', 'gwsfb'),
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'btn_apply_typo',
      'selector' => '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button',
    ]);

    $this->add_control('btn_apply_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('btn_apply_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'btn_apply_border',
      'selector' => '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button',
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('btn_apply_hover', [
      'label' => __('Apply Hover', 'gwsfb'),
    ]);

    $this->add_control('btn_apply_h_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button:hover' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('btn_apply_h_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('btn_apply_h_border', [
      'label'     => __('Border Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__apply.button:hover' => 'border-color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->start_controls_tabs('tabs_btn_reset');

    $this->start_controls_tab('btn_reset_normal', [
      'label' => __('Reset Normal', 'gwsfb'),
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'btn_reset_typo',
      'selector' => '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button',
    ]);

    $this->add_control('btn_reset_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('btn_reset_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'btn_reset_border',
      'selector' => '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button',
    ]);

    $this->end_controls_tab();

    $this->start_controls_tab('btn_reset_hover', [
      'label' => __('Reset Hover', 'gwsfb'),
    ]);

    $this->add_control('btn_reset_h_color', [
      'label'     => __('Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button:hover' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('btn_reset_h_bg', [
      'label'     => __('Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button:hover' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_control('btn_reset_h_border', [
      'label'     => __('Border Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__actions .gwsfb__reset.button:hover' => 'border-color: {{VALUE}};',
      ],
    ]);

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->add_responsive_control('btn_radius', [
      'label'      => __('Buttons Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__actions .button' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('btn_padding', [
      'label'      => __('Buttons Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__actions .button' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->end_controls_section();
  }

  protected function render() {
    $s = $this->get_settings_for_display();
    $id = $s['set_id'] ?? '';
    if (!$id) {
      echo '<div style="color:#b32d2e;">Select a Filter Set.</div>';
      return;
    }

    $group = trim((string)($s['group_key'] ?? ''));
    $group = $group !== '' ? sanitize_key($group) : ('g' . $id);

    $mode_desktop = isset($s['attr_visibility']) && $s['attr_visibility'] !== '' ? $s['attr_visibility'] : 'show_all';
    $mode_tablet  = isset($s['attr_visibility_tablet']) && $s['attr_visibility_tablet'] !== '' ? $s['attr_visibility_tablet'] : $mode_desktop;
    $mode_mobile  = isset($s['attr_visibility_mobile']) && $s['attr_visibility_mobile'] !== '' ? $s['attr_visibility_mobile'] : $mode_tablet;

    $valid_modes = ['show_all', 'hide_first', 'hide_all'];

    if (!in_array($mode_desktop, $valid_modes, true)) $mode_desktop = 'show_all';
    if (!in_array($mode_tablet, $valid_modes, true)) $mode_tablet = $mode_desktop;
    if (!in_array($mode_mobile, $valid_modes, true)) $mode_mobile = $mode_tablet;

    $cfg = [
      'desktop' => $mode_desktop,
      'tablet'  => $mode_tablet,
      'mobile'  => $mode_mobile,
    ];

    if (wp_script_is('gwsfb-config', 'registered')) {
      wp_enqueue_script('gwsfb-config');

      $inline = 'window.GWSFB = window.GWSFB || {};'
        . 'window.GWSFB.attrVisibility = window.GWSFB.attrVisibility || {};'
        . 'window.GWSFB.attrVisibility[' . wp_json_encode($group) . '] = ' . wp_json_encode($cfg) . ';';

      wp_add_inline_script('gwsfb-config', $inline, 'before');
    }

    $view = [];

    $shape = isset($s['loading_spinner_shape']) ? sanitize_key((string)$s['loading_spinner_shape']) : 'circle';
    if (!in_array($shape, ['circle', 'square', 'rounded'], true)) {
      $shape = 'circle';
    }
    $view['loading_shape'] = $shape;

    if (!empty($s['loading_overlay_bg'])) {
      $view['loading_bg'] = $s['loading_overlay_bg'];
    }

    if (!empty($s['loading_spinner_color'])) {
      $view['loading_fg'] = $s['loading_spinner_color'];
    }

    if (isset($s['loading_overlay_opacity']) && $s['loading_overlay_opacity'] !== '') {
      $opacity_raw = $s['loading_overlay_opacity'];
      $opacity_val = is_array($opacity_raw) ? ($opacity_raw['size'] ?? null) : $opacity_raw;
      if ($opacity_val !== null) {
        $view['loading_opacity'] = (float)$opacity_val;
      }
    }

    $icon_source = isset($s['toggle_icon_source']) ? sanitize_key((string)$s['toggle_icon_source']) : 'preset';
    if (!in_array($icon_source, ['preset', 'custom'], true)) {
      $icon_source = 'preset';
    }
    $view['toggle_icon_source'] = $icon_source;

    $preset = isset($s['toggle_icon_preset']) ? sanitize_key((string)$s['toggle_icon_preset']) : 'plusminus';
    if (!in_array($preset, ['plusminus', 'chevron', 'caret', 'arrow'], true)) {
      $preset = 'plusminus';
    }
    $view['toggle_icon_preset'] = $preset;

    $collapsed_icon = (isset($s['toggle_icon_collapsed']) && is_array($s['toggle_icon_collapsed'])) ? $s['toggle_icon_collapsed'] : [];
    $expanded_icon  = (isset($s['toggle_icon_expanded']) && is_array($s['toggle_icon_expanded'])) ? $s['toggle_icon_expanded'] : [];

    $view['toggle_icon_collapsed'] = $collapsed_icon;
    $view['toggle_icon_expanded']  = $expanded_icon;

    $has_custom_icons = ($icon_source === 'custom') && (!empty($collapsed_icon['value']) || !empty($expanded_icon['value']));

    $view['attr_visibility_initial'] = $mode_desktop;

    $shell_classes = [
      'gwsfb-filter-shell',
      'gwsfb-toggle-preset-' . $preset,
    ];

    if ($has_custom_icons) {
      $shell_classes[] = 'gwsfb-has-custom-toggle-icons';
    }

    echo '<div class="' . esc_attr(implode(' ', $shell_classes)) . '">';
    echo GWSFB_Render::render_filters_only($id, $group, $view);
    echo '</div>';
  }
}