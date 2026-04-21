<?php
if (!defined('ABSPATH')) exit;

trait GWSFB_Elementor_Sort_Controls {

  protected function register_sort_controls_section() {
    $this->start_controls_section('section_sort_controls', [
      'label' => __('Sort', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
    ]);

    $this->add_control('sort_enabled', [
      'label'        => __('Show Sort Dropdown', 'gwsfb'),
      'type'         => \Elementor\Controls_Manager::SWITCHER,
      'label_on'     => __('Show', 'gwsfb'),
      'label_off'    => __('Hide', 'gwsfb'),
      'return_value' => '1',
      'default'      => '1',
    ]);

    $this->add_control('sort_label_show', [
      'label'        => __('Show Sort Label', 'gwsfb'),
      'type'         => \Elementor\Controls_Manager::SWITCHER,
      'label_on'     => __('Show', 'gwsfb'),
      'label_off'    => __('Hide', 'gwsfb'),
      'return_value' => '1',
      'default'      => '',
      'condition'    => [
        'sort_enabled' => '1',
      ],
    ]);

    $this->add_control('sort_label_text', [
      'label'     => __('Sort Label Text', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::TEXT,
      'default'   => 'Sort',
      'condition' => [
        'sort_enabled'    => '1',
        'sort_label_show' => '1',
      ],
    ]);

    $this->add_control('sort_summary_layout', [
      'label'   => __('Top Bar Layout', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'summary_left_sort_right' => __('Summary Left / Sort Right', 'gwsfb'),
        'sort_left_summary_right' => __('Sort Left / Summary Right', 'gwsfb'),
      ],
      'default' => 'summary_left_sort_right',
    ]);

    $this->end_controls_section();
  }

  protected function register_sort_style_controls_section() {
    $this->start_controls_section('section_sort_style_controls', [
      'label' => __('Sort Bar', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    ]);

    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
      'name'     => 'sort_bar_bg',
      'types'    => ['classic', 'gradient'],
      'selector' => '{{WRAPPER}} .gwsfb__sort',
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'sort_bar_border',
      'selector' => '{{WRAPPER}} .gwsfb__sort',
    ]);

    $this->add_responsive_control('sort_bar_radius', [
      'label'      => __('Bar Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__sort' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('sort_bar_padding', [
      'label'      => __('Bar Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__sort' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('sort_bar_gap', [
      'label'      => __('Bar Gap', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px'],
      'range'      => [
        'px' => ['min' => 0, 'max' => 40],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__sort' => 'gap: {{SIZE}}{{UNIT}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sort_bar_label_typo',
      'selector' => '{{WRAPPER}} .gwsfb__sortlabel, {{WRAPPER}} .gwsfb__sortlabel *',
    ]);

    $this->add_control('sort_bar_label_color', [
      'label'     => __('Label Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__sortlabel'   => 'color: {{VALUE}};',
        '{{WRAPPER}} .gwsfb__sortlabel *' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
      'name'     => 'sort_bar_select_typo',
      'selector' => '{{WRAPPER}} .gwsfb__sortselect',
    ]);

    $this->add_control('sort_bar_select_color', [
      'label'     => __('Dropdown Text Color', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__sortselect' => 'color: {{VALUE}};',
      ],
    ]);

    $this->add_control('sort_bar_select_bg', [
      'label'     => __('Dropdown Background', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::COLOR,
      'selectors' => [
        '{{WRAPPER}} .gwsfb__sortselect' => 'background-color: {{VALUE}};',
      ],
    ]);

    $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
      'name'     => 'sort_bar_select_border',
      'selector' => '{{WRAPPER}} .gwsfb__sortselect',
    ]);

    $this->add_responsive_control('sort_bar_select_radius', [
      'label'      => __('Dropdown Border Radius', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__sortselect' =>
          'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
      ],
    ]);

    $this->add_responsive_control('sort_bar_select_padding', [
      'label'      => __('Dropdown Padding', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::DIMENSIONS,
      'size_units' => ['px', '%'],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__sortselect' =>
          'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; height:auto; min-height:0; line-height:normal; box-sizing:border-box;',
      ],
    ]);

    $this->add_responsive_control('sort_bar_select_width', [
      'label'      => __('Dropdown Width', 'gwsfb'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => ['px', '%'],
      'range'      => [
        'px' => ['min' => 80, 'max' => 500],
        '%'  => ['min' => 10, 'max' => 100],
      ],
      'selectors'  => [
        '{{WRAPPER}} .gwsfb__sortselect' => 'width: {{SIZE}}{{UNIT}}; max-width: 100%; min-width: 0;',
      ],
    ]);

    $this->end_controls_section();
  }
}