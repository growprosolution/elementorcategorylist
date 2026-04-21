<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('\Elementor\Widget_Base')) {
  return;
}

require_once __DIR__ . '/elementor/trait-gwsfb-elementor-results-content.php';
require_once __DIR__ . '/elementor/trait-gwsfb-elementor-results-style.php';
require_once __DIR__ . '/elementor/trait-gwsfb-elementor-sort.php';

class GWSFB_Elementor_Widget_Results extends \Elementor\Widget_Base {

  use GWSFB_Elementor_Results_Content_Controls;
  use GWSFB_Elementor_Results_Style_Controls;
  use GWSFB_Elementor_Sort_Controls;

  public function get_name() {
    return 'gwsfb_products_results';
  }

  public function get_title() {
    return 'GWS Products Results';
  }

  public function get_icon() {
    return 'eicon-products';
  }

  public function get_categories() {
    return ['general'];
  }

  public function get_keywords() {
    return ['woo', 'products', 'results', 'filter', 'grid', 'list', 'gws'];
  }

  protected function register_controls() {
    $this->register_results_content_controls();
    $this->register_image_placeholder_controls();

    if (method_exists($this, 'register_sort_controls_section')) {
      $this->register_sort_controls_section();
    }

    $this->register_results_style_controls();

    if (method_exists($this, 'register_sort_style_controls_section')) {
      $this->register_sort_style_controls_section();
    }
  }

  private function register_image_placeholder_controls() {
    $this->start_controls_section('section_image_placeholder', [
      'label' => __('Image Placeholder', 'gwsfb'),
      'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
    ]);

    $this->add_control('image_placeholder_type', [
      'label'   => __('Placeholder Type', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'text'  => __('Text', 'gwsfb'),
        'image' => __('Image', 'gwsfb'),
      ],
      'default' => 'text',
    ]);

    $this->add_control('image_placeholder_media', [
      'label'     => __('Placeholder Image', 'gwsfb'),
      'type'      => \Elementor\Controls_Manager::MEDIA,
      'condition' => [
        'image_placeholder_type' => 'image',
      ],
    ]);

    $this->add_control('image_placeholder_text', [
      'label'       => __('Placeholder Text', 'gwsfb'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => 'Image',
      'placeholder' => 'Image',
      'condition'   => [
        'image_placeholder_type' => 'text',
      ],
    ]);

    $this->add_control('image_placeholder_dismiss', [
      'label'   => __('Dismiss Animation', 'gwsfb'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'options' => [
        'none'       => __('None', 'gwsfb'),
        'fade'       => __('Fade out', 'gwsfb'),
        'fade_left'  => __('Fade out left', 'gwsfb'),
        'fade_right' => __('Fade out right', 'gwsfb'),
      ],
      'default' => 'fade',
    ]);

    $this->end_controls_section();
  }

  private function sanitize_choice($value, array $allowed, string $fallback): string {
    $value = sanitize_key((string)$value);
    return in_array($value, $allowed, true) ? $value : $fallback;
  }

  private function normalize_switcher($value): int {
    return !empty($value) ? 1 : 0;
  }

  private function get_responsive_switcher(array $m, string $base, int $desktop_default = 0, ?int $tablet_default = null, ?int $mobile_default = null): array {
    $desktop = array_key_exists($base, $m) ? $this->normalize_switcher($m[$base]) : $desktop_default;
    $tablet  = array_key_exists($base . '_tablet', $m) ? $this->normalize_switcher($m[$base . '_tablet']) : ($tablet_default !== null ? $tablet_default : $desktop);
    $mobile  = array_key_exists($base . '_mobile', $m) ? $this->normalize_switcher($m[$base . '_mobile']) : ($mobile_default !== null ? $mobile_default : $tablet);

    return [$desktop, $tablet, $mobile];
  }

  private function get_responsive_number(array $m, string $base, int $desktop_default, ?int $tablet_default = null, ?int $mobile_default = null): array {
    $desktop = array_key_exists($base, $m) && $m[$base] !== '' && $m[$base] !== null ? (int)$m[$base] : $desktop_default;
    $tablet  = array_key_exists($base . '_tablet', $m) && $m[$base . '_tablet'] !== '' && $m[$base . '_tablet'] !== null ? (int)$m[$base . '_tablet'] : ($tablet_default !== null ? $tablet_default : $desktop);
    $mobile  = array_key_exists($base . '_mobile', $m) && $m[$base . '_mobile'] !== '' && $m[$base . '_mobile'] !== null ? (int)$m[$base . '_mobile'] : ($mobile_default !== null ? $mobile_default : $tablet);

    return [$desktop, $tablet, $mobile];
  }

  private function get_responsive_choice(array $m, string $base, string $desktop_default, array $allowed, ?string $tablet_default = null, ?string $mobile_default = null): array {
    $desktop = array_key_exists($base, $m) && $m[$base] !== '' && $m[$base] !== null
      ? $this->sanitize_choice($m[$base], $allowed, $desktop_default)
      : $desktop_default;

    $tablet = array_key_exists($base . '_tablet', $m) && $m[$base . '_tablet'] !== '' && $m[$base . '_tablet'] !== null
      ? $this->sanitize_choice($m[$base . '_tablet'], $allowed, $desktop)
      : ($tablet_default !== null ? $this->sanitize_choice($tablet_default, $allowed, $desktop) : $desktop);

    $mobile = array_key_exists($base . '_mobile', $m) && $m[$base . '_mobile'] !== '' && $m[$base . '_mobile'] !== null
      ? $this->sanitize_choice($m[$base . '_mobile'], $allowed, $tablet)
      : ($mobile_default !== null ? $this->sanitize_choice($mobile_default, $allowed, $tablet) : $tablet);

    return [$desktop, $tablet, $mobile];
  }

  private function get_responsive_slider_size(array $m, string $base, float $desktop_default, float $min, float $max, ?float $tablet_default = null, ?float $mobile_default = null): array {
    $desktop_raw = array_key_exists($base, $m) ? $m[$base] : null;
    $tablet_raw  = array_key_exists($base . '_tablet', $m) ? $m[$base . '_tablet'] : null;
    $mobile_raw  = array_key_exists($base . '_mobile', $m) ? $m[$base . '_mobile'] : null;

    $desktop = is_array($desktop_raw) && isset($desktop_raw['size']) ? (float)$desktop_raw['size'] : $desktop_default;
    $tablet  = is_array($tablet_raw) && isset($tablet_raw['size']) ? (float)$tablet_raw['size'] : ($tablet_default !== null ? $tablet_default : $desktop);
    $mobile  = is_array($mobile_raw) && isset($mobile_raw['size']) ? (float)$mobile_raw['size'] : ($mobile_default !== null ? $mobile_default : $tablet);

    $desktop = max($min, min($max, $desktop));
    $tablet  = max($min, min($max, $tablet));
    $mobile  = max($min, min($max, $mobile));

    return [$desktop, $tablet, $mobile];
  }

  private function sanitize_layout_modules(array $s, array $set): array {
    $cols_fallback = (int)($set['results']['columns'] ?? 4);
    if ($cols_fallback < 1) $cols_fallback = 4;
    if ($cols_fallback > 6) $cols_fallback = 6;

    $mods = $s['layout_modules'] ?? [];
    if (!is_array($mods)) $mods = [];

    $out = [];

    foreach ($mods as $m) {
      if (!is_array($m)) continue;

      $rid = isset($m['_id']) ? preg_replace('/[^a-zA-Z0-9\_\-]/', '', (string)$m['_id']) : '';
      if ($rid === '') continue;

      $type = isset($m['layout_type']) ? sanitize_key((string)$m['layout_type']) : 'grid';
      if (!in_array($type, ['grid', 'list'], true)) $type = 'grid';

      $label = isset($m['layout_label']) ? sanitize_text_field((string)$m['layout_label']) : '';
      $icon  = (isset($m['layout_icon']) && is_array($m['layout_icon'])) ? $m['layout_icon'] : [];

      $colsDesktop = $cols_fallback;
      $colsTablet  = $cols_fallback;
      $colsMobile  = 1;

      $rowsDesktop = 0;
      $rowsTablet  = 0;
      $rowsMobile  = 0;

      if ($type === 'grid') {
        [$colsDesktop, $colsTablet, $colsMobile] = $this->get_responsive_number($m, 'grid_columns', $cols_fallback, 2, 1);

        $colsDesktop = max(1, min(6, $colsDesktop));
        $colsTablet  = max(1, min(6, $colsTablet));
        $colsMobile  = max(1, min(6, $colsMobile));

        [$rowsDesktop, $rowsTablet, $rowsMobile] = $this->get_responsive_number($m, 'grid_rows', 0, 0, 0);

        $rowsDesktop = max(0, $rowsDesktop);
        $rowsTablet  = max(0, $rowsTablet);
        $rowsMobile  = max(0, $rowsMobile);
      } else {
        $colsDesktop = 1;
        $colsTablet  = 1;
        $colsMobile  = 1;

        [$rowsDesktop, $rowsTablet, $rowsMobile] = $this->get_responsive_number($m, 'list_rows', 0, 0, 0);

        $rowsDesktop = max(0, $rowsDesktop);
        $rowsTablet  = max(0, $rowsTablet);
        $rowsMobile  = max(0, $rowsMobile);
      }

      [$showTitleDesktop, $showTitleTablet, $showTitleMobile] = $this->get_responsive_switcher($m, 'show_title', 1, 1, 1);
      [$showPriceDesktop, $showPriceTablet, $showPriceMobile] = $this->get_responsive_switcher($m, 'show_price', 1, 1, 1);
      [$showRatingDesktop, $showRatingTablet, $showRatingMobile] = $this->get_responsive_switcher($m, 'show_rating', 1, 1, 1);
      [$showAddToCartDesktop, $showAddToCartTablet, $showAddToCartMobile] = $this->get_responsive_switcher($m, 'show_add_to_cart', 1, 1, 1);
      [$showDescriptionDesktop, $showDescriptionTablet, $showDescriptionMobile] = $this->get_responsive_switcher($m, 'show_description', 0, 0, 0);
      [$showViewMoreDesktop, $showViewMoreTablet, $showViewMoreMobile] = $this->get_responsive_switcher($m, 'show_view_more', 1, 1, 1);

      $view_more_label = isset($m['view_more_label']) && $m['view_more_label'] !== ''
        ? sanitize_text_field((string)$m['view_more_label'])
        : 'View more';

      $options_enable = (!empty($m['options_enable']) && $type === 'grid' && $showAddToCartDesktop) ? 1 : 0;
      $options_open   = (!empty($m['options_open']) && $options_enable) ? 1 : 0;
      $options_label  = isset($m['options_label']) && $m['options_label'] !== ''
        ? sanitize_text_field((string)$m['options_label'])
        : 'Options';

      [$listMobileLayoutDesktop, $listMobileLayoutTablet, $listMobileLayoutMobile] = $this->get_responsive_choice(
        $m,
        'list_mobile_layout',
        'row',
        ['row', 'column'],
        'row',
        'column'
      );

      [$listMobileImageWidthDesktop, $listMobileImageWidthTablet, $listMobileImageWidthMobile] = $this->get_responsive_slider_size(
        $m,
        'list_mobile_image_width',
        28,
        15,
        80,
        34,
        40
      );

      [$listMobileVerticalAlignDesktop, $listMobileVerticalAlignTablet, $listMobileVerticalAlignMobile] = $this->get_responsive_choice(
        $m,
        'list_mobile_vertical_align',
        'flex-start',
        ['flex-start', 'center', 'flex-end'],
        'flex-start',
        'flex-start'
      );

      [$titleAlignDesktop, $titleAlignTablet, $titleAlignMobile] = $this->get_responsive_choice(
        $m,
        'title_align',
        'left',
        ['left', 'center', 'right'],
        'left',
        'left'
      );

      [$descAlignDesktop, $descAlignTablet, $descAlignMobile] = $this->get_responsive_choice(
        $m,
        'desc_align',
        'left',
        ['left', 'center', 'right'],
        'left',
        'left'
      );

      [$priceAlignDesktop, $priceAlignTablet, $priceAlignMobile] = $this->get_responsive_choice(
        $m,
        'price_align',
        'left',
        ['left', 'center', 'right'],
        'left',
        'left'
      );

      [$moreAlignDesktop, $moreAlignTablet, $moreAlignMobile] = $this->get_responsive_choice(
        $m,
        'more_align',
        'left',
        ['left', 'center', 'right'],
        'left',
        'left'
      );

      [$cardJustifyDesktop, $cardJustifyTablet, $cardJustifyMobile] = $this->get_responsive_choice(
        $m,
        'sty_card_justify_content',
        '',
        ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
        '',
        ''
      );

      $out[] = [
        'id'                                 => $rid,
        'type'                               => $type,
        'label'                              => $label,
        'icon'                               => $icon,
        'columns'                            => ($type === 'list') ? 1 : $colsDesktop,
        'rows'                               => $rowsDesktop,
        'columns_desktop'                    => ($type === 'list') ? 1 : $colsDesktop,
        'columns_tablet'                     => ($type === 'list') ? 1 : $colsTablet,
        'columns_mobile'                     => ($type === 'list') ? 1 : $colsMobile,
        'rows_desktop'                       => $rowsDesktop,
        'rows_tablet'                        => $rowsTablet,
        'rows_mobile'                        => $rowsMobile,

        'show_title'                         => $showTitleDesktop,
        'show_title_desktop'                 => $showTitleDesktop,
        'show_title_tablet'                  => $showTitleTablet,
        'show_title_mobile'                  => $showTitleMobile,

        'show_price'                         => $showPriceDesktop,
        'show_price_desktop'                 => $showPriceDesktop,
        'show_price_tablet'                  => $showPriceTablet,
        'show_price_mobile'                  => $showPriceMobile,

        'show_rating'                        => $showRatingDesktop,
        'show_rating_desktop'                => $showRatingDesktop,
        'show_rating_tablet'                 => $showRatingTablet,
        'show_rating_mobile'                 => $showRatingMobile,

        'show_add_to_cart'                   => $showAddToCartDesktop,
        'show_add_to_cart_desktop'           => $showAddToCartDesktop,
        'show_add_to_cart_tablet'            => $showAddToCartTablet,
        'show_add_to_cart_mobile'            => $showAddToCartMobile,

        'show_description'                   => $showDescriptionDesktop,
        'show_description_desktop'           => $showDescriptionDesktop,
        'show_description_tablet'            => $showDescriptionTablet,
        'show_description_mobile'            => $showDescriptionMobile,

        'show_view_more'                     => $showViewMoreDesktop,
        'show_view_more_desktop'             => $showViewMoreDesktop,
        'show_view_more_tablet'              => $showViewMoreTablet,
        'show_view_more_mobile'              => $showViewMoreMobile,

        'view_more_label'                    => $view_more_label,
        'options_enable'                     => $options_enable,
        'options_open'                       => $options_open,
        'options_label'                      => $options_label,

        'list_mobile_layout'                 => $listMobileLayoutDesktop,
        'list_mobile_layout_desktop'         => $listMobileLayoutDesktop,
        'list_mobile_layout_tablet'          => $listMobileLayoutTablet,
        'list_mobile_layout_mobile'          => $listMobileLayoutMobile,

        'list_mobile_image_width'            => $listMobileImageWidthDesktop,
        'list_mobile_image_width_desktop'    => $listMobileImageWidthDesktop,
        'list_mobile_image_width_tablet'     => $listMobileImageWidthTablet,
        'list_mobile_image_width_mobile'     => $listMobileImageWidthMobile,

        'list_mobile_vertical_align'         => $listMobileVerticalAlignDesktop,
        'list_mobile_vertical_align_desktop' => $listMobileVerticalAlignDesktop,
        'list_mobile_vertical_align_tablet'  => $listMobileVerticalAlignTablet,
        'list_mobile_vertical_align_mobile'  => $listMobileVerticalAlignMobile,

        'title_align'                        => $titleAlignDesktop,
        'title_align_desktop'                => $titleAlignDesktop,
        'title_align_tablet'                 => $titleAlignTablet,
        'title_align_mobile'                 => $titleAlignMobile,

        'desc_align'                         => $descAlignDesktop,
        'desc_align_desktop'                 => $descAlignDesktop,
        'desc_align_tablet'                  => $descAlignTablet,
        'desc_align_mobile'                  => $descAlignMobile,

        'price_align'                        => $priceAlignDesktop,
        'price_align_desktop'                => $priceAlignDesktop,
        'price_align_tablet'                 => $priceAlignTablet,
        'price_align_mobile'                 => $priceAlignMobile,

        'more_align'                         => $moreAlignDesktop,
        'more_align_desktop'                 => $moreAlignDesktop,
        'more_align_tablet'                  => $moreAlignTablet,
        'more_align_mobile'                  => $moreAlignMobile,

        'card_justify_content'               => $cardJustifyDesktop,
        'card_justify_content_desktop'       => $cardJustifyDesktop,
        'card_justify_content_tablet'        => $cardJustifyTablet,
        'card_justify_content_mobile'        => $cardJustifyMobile,
      ];
    }

    if (empty($out)) {
      $out[] = [
        'id'                                 => 'default_grid',
        'type'                               => 'grid',
        'label'                              => 'Grid',
        'icon'                               => [],
        'columns'                            => $cols_fallback,
        'rows'                               => 0,
        'columns_desktop'                    => $cols_fallback,
        'columns_tablet'                     => min(6, max(1, $cols_fallback)),
        'columns_mobile'                     => 1,
        'rows_desktop'                       => 0,
        'rows_tablet'                        => 0,
        'rows_mobile'                        => 0,

        'show_title'                         => 1,
        'show_title_desktop'                 => 1,
        'show_title_tablet'                  => 1,
        'show_title_mobile'                  => 1,

        'show_price'                         => 1,
        'show_price_desktop'                 => 1,
        'show_price_tablet'                  => 1,
        'show_price_mobile'                  => 1,

        'show_rating'                        => 1,
        'show_rating_desktop'                => 1,
        'show_rating_tablet'                 => 1,
        'show_rating_mobile'                 => 1,

        'show_add_to_cart'                   => 1,
        'show_add_to_cart_desktop'           => 1,
        'show_add_to_cart_tablet'            => 1,
        'show_add_to_cart_mobile'            => 1,

        'show_description'                   => 0,
        'show_description_desktop'           => 0,
        'show_description_tablet'            => 0,
        'show_description_mobile'            => 0,

        'show_view_more'                     => 1,
        'show_view_more_desktop'             => 1,
        'show_view_more_tablet'              => 1,
        'show_view_more_mobile'              => 1,

        'view_more_label'                    => 'View more',
        'options_enable'                     => 1,
        'options_open'                       => 0,
        'options_label'                      => 'Options',

        'list_mobile_layout'                 => 'row',
        'list_mobile_layout_desktop'         => 'row',
        'list_mobile_layout_tablet'          => 'row',
        'list_mobile_layout_mobile'          => 'column',

        'list_mobile_image_width'            => 28,
        'list_mobile_image_width_desktop'    => 28,
        'list_mobile_image_width_tablet'     => 34,
        'list_mobile_image_width_mobile'     => 40,

        'list_mobile_vertical_align'         => 'flex-start',
        'list_mobile_vertical_align_desktop' => 'flex-start',
        'list_mobile_vertical_align_tablet'  => 'flex-start',
        'list_mobile_vertical_align_mobile'  => 'flex-start',

        'title_align'                        => 'left',
        'title_align_desktop'                => 'left',
        'title_align_tablet'                 => 'left',
        'title_align_mobile'                 => 'left',

        'desc_align'                         => 'left',
        'desc_align_desktop'                 => 'left',
        'desc_align_tablet'                  => 'left',
        'desc_align_mobile'                  => 'left',

        'price_align'                        => 'left',
        'price_align_desktop'                => 'left',
        'price_align_tablet'                 => 'left',
        'price_align_mobile'                 => 'left',

        'more_align'                         => 'left',
        'more_align_desktop'                 => 'left',
        'more_align_tablet'                  => 'left',
        'more_align_mobile'                  => 'left',

        'card_justify_content'               => '',
        'card_justify_content_desktop'       => '',
        'card_justify_content_tablet'        => '',
        'card_justify_content_mobile'        => '',
      ];
    }

    return $out;
  }

  protected function render() {
    $s  = $this->get_settings_for_display();
    $id = $s['set_id'] ?? '';

    if (!$id) {
      echo '<div style="color:#b32d2e;">Select a Filter Set.</div>';
      return;
    }

    $set = GWSFB_Helpers::get_set($id);
    if (!$set || empty($set['enabled'])) {
      echo '<div style="color:#b32d2e;">Filter Set not found or disabled.</div>';
      return;
    }

    $group = trim((string)($s['group_key'] ?? ''));
    $group = $group !== '' ? sanitize_key($group) : ('g' . $id);

    $layouts = $this->sanitize_layout_modules($s, $set);
    $active_layout = $layouts[0];
    $active_layout_id = $active_layout['id'];
    $active_cols = ($active_layout['type'] === 'list') ? 1 : (int)$active_layout['columns'];

    $view = [
      'layout'                             => $active_layout_id,
      'layouts'                            => $layouts,
      'list_mobile_layout'                 => $active_layout['list_mobile_layout'],
      'list_mobile_layout_desktop'         => $active_layout['list_mobile_layout_desktop'],
      'list_mobile_layout_tablet'          => $active_layout['list_mobile_layout_tablet'],
      'list_mobile_layout_mobile'          => $active_layout['list_mobile_layout_mobile'],
      'list_mobile_image_width'            => $active_layout['list_mobile_image_width'],
      'list_mobile_image_width_desktop'    => $active_layout['list_mobile_image_width_desktop'],
      'list_mobile_image_width_tablet'     => $active_layout['list_mobile_image_width_tablet'],
      'list_mobile_image_width_mobile'     => $active_layout['list_mobile_image_width_mobile'],
      'list_mobile_vertical_align'         => $active_layout['list_mobile_vertical_align'],
      'list_mobile_vertical_align_desktop' => $active_layout['list_mobile_vertical_align_desktop'],
      'list_mobile_vertical_align_tablet'  => $active_layout['list_mobile_vertical_align_tablet'],
      'list_mobile_vertical_align_mobile'  => $active_layout['list_mobile_vertical_align_mobile'],
      'title_align'                        => $active_layout['title_align'],
      'title_align_desktop'                => $active_layout['title_align_desktop'],
      'title_align_tablet'                 => $active_layout['title_align_tablet'],
      'title_align_mobile'                 => $active_layout['title_align_mobile'],
      'desc_align'                         => $active_layout['desc_align'],
      'desc_align_desktop'                 => $active_layout['desc_align_desktop'],
      'desc_align_tablet'                  => $active_layout['desc_align_tablet'],
      'desc_align_mobile'                  => $active_layout['desc_align_mobile'],
      'price_align'                        => $active_layout['price_align'],
      'price_align_desktop'                => $active_layout['price_align_desktop'],
      'price_align_tablet'                 => $active_layout['price_align_tablet'],
      'price_align_mobile'                 => $active_layout['price_align_mobile'],
      'more_align'                         => $active_layout['more_align'],
      'more_align_desktop'                 => $active_layout['more_align_desktop'],
      'more_align_tablet'                  => $active_layout['more_align_tablet'],
      'more_align_mobile'                  => $active_layout['more_align_mobile'],
      'card_justify_content'               => $active_layout['card_justify_content'],
      'card_justify_content_desktop'       => $active_layout['card_justify_content_desktop'],
      'card_justify_content_tablet'        => $active_layout['card_justify_content_tablet'],
      'card_justify_content_mobile'        => $active_layout['card_justify_content_mobile'],
    ];

    $view['show_summary'] = !empty($s['show_summary']) ? 1 : 0;

    $tag = isset($s['title_html_tag']) ? strtolower((string)$s['title_html_tag']) : 'h2';
    $allowed_tags = ['h1','h2','h3','h4','h5','h6','p','span','div'];
    if (!in_array($tag, $allowed_tags, true)) {
      $tag = 'h2';
    }
    $view['title_tag'] = $tag;

    $sort_enabled = 1;
    if (isset($s['sort_enabled'])) {
      $sort_enabled = !empty($s['sort_enabled']) ? 1 : 0;
    } elseif (isset($s['show_sort_dropdown'])) {
      $sort_enabled = !empty($s['show_sort_dropdown']) ? 1 : 0;
    }
    $view['sort_enabled'] = $sort_enabled;

    if (isset($s['show_sort_dropdown'])) {
      $view['show_sort_dropdown'] = !empty($s['show_sort_dropdown']) ? 1 : 0;
    }

    $view['sort_label_show'] = !empty($s['sort_label_show']) ? 1 : 0;

    $label_txt = isset($s['sort_label_text']) && $s['sort_label_text'] !== ''
      ? $s['sort_label_text']
      : 'Sort';
    $view['sort_label_text'] = sanitize_text_field($label_txt);

    $layout_mode = isset($s['sort_summary_layout'])
      ? sanitize_key((string)$s['sort_summary_layout'])
      : 'summary_left_sort_right';
    if (!in_array($layout_mode, ['summary_left_sort_right', 'sort_left_summary_right'], true)) {
      $layout_mode = 'summary_left_sort_right';
    }
    $view['sort_summary_layout'] = $layout_mode;

    $shape = isset($s['loading_spinner_shape'])
      ? sanitize_key((string)$s['loading_spinner_shape'])
      : 'circle';
    if (!in_array($shape, ['circle', 'square', 'rounded'], true)) $shape = 'circle';
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

    $placeholder_type = isset($s['image_placeholder_type']) ? sanitize_key((string)$s['image_placeholder_type']) : 'text';
    if (!in_array($placeholder_type, ['text', 'image'], true)) {
      $placeholder_type = 'text';
    }

    $view['image_placeholder_mode'] = $placeholder_type;

    $ph_id  = !empty($s['image_placeholder_media']['id']) ? absint($s['image_placeholder_media']['id']) : 0;
    $ph_url = !empty($s['image_placeholder_media']['url']) ? esc_url_raw($s['image_placeholder_media']['url']) : '';

    $view['image_placeholder_image'] = [
      'id'  => $ph_id,
      'url' => $ph_url,
    ];

    $placeholder_text = isset($s['image_placeholder_text']) && $s['image_placeholder_text'] !== ''
      ? sanitize_text_field((string)$s['image_placeholder_text'])
      : 'Image';
    $view['image_placeholder_text'] = $placeholder_text;

    $dismiss = isset($s['image_placeholder_dismiss']) ? sanitize_key((string)$s['image_placeholder_dismiss']) : 'fade';
    if (!in_array($dismiss, ['none', 'fade', 'fade_left', 'fade_right'], true)) {
      $dismiss = 'fade';
    }
    $view['image_placeholder_dismiss'] = $dismiss;

    echo GWSFB_Render::render_results_only($id, $group, $active_cols, $view);
  }
}