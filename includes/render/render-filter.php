<?php
if (!defined('ABSPATH')) exit;

trait GWSFB_Render_Filters {

  private static function render_filter_icon_html($icon_setting): string {
    if (!is_array($icon_setting) || empty($icon_setting['value']) || !class_exists('\Elementor\Icons_Manager')) {
      return '';
    }

    ob_start();
    try {
      \Elementor\Icons_Manager::render_icon($icon_setting, ['aria-hidden' => 'true']);
    } catch (\Throwable $e) {
    }

    return trim((string)ob_get_clean());
  }

private static function format_price_label($value): string {
  $value = (float)$value;

  if ((float)(int)$value === $value) {
    $number = (string)(int)$value;
  } else {
    $number = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
  }

  $symbol = function_exists('get_woocommerce_currency_symbol')
    ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8')
    : '$';

  $pos = get_option('woocommerce_currency_pos', 'left');

  switch ($pos) {
    case 'left':
      return $symbol . $number;

    case 'right':
      return $number . $symbol;

    case 'left_space':
      return $symbol . ' ' . $number;

    case 'right_space':
      return $number . ' ' . $symbol;

    default:
      return $symbol . $number;
  }
}

  private static function round_floor_step(float $v, float $step): float {
    if ($step <= 0) return $v;
    return floor($v / $step) * $step;
  }

  private static function round_ceil_step(float $v, float $step): float {
    if ($step <= 0) return $v;
    return ceil($v / $step) * $step;
  }

  private static function get_auto_price_range(array $set): array {
    $args = GWSFB_Query::build_args($set, [
      'page' => 1,
    ]);

    $args['posts_per_page']         = -1;
    $args['paged']                  = 1;
    $args['fields']                 = 'ids';
    $args['no_found_rows']          = true;
    $args['update_post_meta_cache'] = false;
    $args['update_post_term_cache'] = false;

    unset($args['offset']);

    $q = new WP_Query($args);

    $min = null;
    $max = null;
    $has_missing_price = false;

    if (!empty($q->posts) && is_array($q->posts)) {
      foreach ($q->posts as $pid) {
        $price_raw = get_post_meta((int)$pid, '_price', true);

        if ($price_raw === '' || $price_raw === null) {
          $has_missing_price = true;
          continue;
        }

        $price = (float)$price_raw;

        if ($min === null || $price < $min) $min = $price;
        if ($max === null || $price > $max) $max = $price;
      }
    }

    wp_reset_postdata();

    if ($min === null && $max === null) {
      return ['min' => 0.0, 'max' => 0.0];
    }

    if ($has_missing_price) {
      if ($min === null || $min > 0.0) $min = 0.0;
      if ($max === null) $max = 0.0;
    }

    return ['min' => (float)$min, 'max' => (float)$max];
  }

  private static function get_attr_visibility_initial_mode(array $view = []): string {
    $mode = isset($view['attr_visibility_initial']) ? sanitize_key((string)$view['attr_visibility_initial']) : 'show_all';
    if (!in_array($mode, ['show_all', 'hide_first', 'hide_all'], true)) {
      $mode = 'show_all';
    }
    return $mode;
  }

  private static function is_attr_block_initially_open(array $view, int $index): bool {
    $mode = self::get_attr_visibility_initial_mode($view);

    if ($mode === 'hide_all') {
      return false;
    }

    if ($mode === 'hide_first') {
      return $index === 0;
    }

    return true;
  }

  private static function render_block_header(string $label, array $view = [], bool $is_open = true): void {
    $icon_source = isset($view['toggle_icon_source']) ? sanitize_key((string)$view['toggle_icon_source']) : 'preset';
    if (!in_array($icon_source, ['preset', 'custom'], true)) {
      $icon_source = 'preset';
    }

    $collapsed_html = '';
    $expanded_html  = '';

    if ($icon_source === 'custom') {
      $collapsed_html = self::render_filter_icon_html($view['toggle_icon_collapsed'] ?? []);
      $expanded_html  = self::render_filter_icon_html($view['toggle_icon_expanded'] ?? []);
    }

    if ($collapsed_html === '' && $expanded_html !== '') {
      $collapsed_html = $expanded_html;
    }
    if ($expanded_html === '' && $collapsed_html !== '') {
      $expanded_html = $collapsed_html;
    }

    $has_custom = ($icon_source === 'custom') && ($collapsed_html !== '' || $expanded_html !== '');

    echo '<div class="gwsfb__titlebar" role="button" tabindex="0" aria-expanded="' . ($is_open ? 'true' : 'false') . '">';
      echo '<span class="gwsfb__title"><span class="gwsfb__title-text">' . esc_html($label) . '</span></span>';
      echo '<span class="gwsfb__toggle-icon gwsfb__title-icon' . ($has_custom ? ' has-custom-icons' : '') . '" aria-hidden="true">';
        if ($has_custom) {
          echo '<span class="gwsfb__toggle-icon-collapsed">' . $collapsed_html . '</span>';
          echo '<span class="gwsfb__toggle-icon-expanded">' . $expanded_html . '</span>';
        }
      echo '</span>';
    echo '</div>';
  }

  public static function render_filters(array $set, array $view = []): void {
    foreach (($set['filters'] ?? []) as $f) {
      if (empty($f['enabled'])) continue;

      $type = $f['type'] ?? '';

      if ($type === 'category')  self::filter_category($f, $view, $set);
      if ($type === 'attribute') self::filter_attributes($f, $view, $set);
      if ($type === 'price')     self::filter_price($f, $view, $set);
      if ($type === 'stock')     self::filter_stock($f, $view, $set);
    }

    echo '<div class="gwsfb__actions">';
      echo '<button type="button" class="gwsfb__apply button">Apply</button>';
      echo '<button type="button" class="gwsfb__reset button">Reset</button>';
    echo '</div>';
  }

  private static function filter_category(array $cfg, array $view = [], array $set = []): void {
    $hide_empty  = !empty($cfg['hide_empty']);
    $parent_only = !empty($cfg['parent_only']);
    $multiple    = !empty($cfg['multiple']);

    $args = [
      'taxonomy'   => 'product_cat',
      'hide_empty' => $hide_empty,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ];

    if ($parent_only) {
      $args['parent'] = 0;
    }

    $terms = get_terms($args);

    echo '<div class="gwsfb__block is-open" data-filter="category">';
      self::render_block_header('Category', $view, true);
      echo '<div class="gwsfb__body">';

        if (is_wp_error($terms) || empty($terms)) {
          echo '<div class="gwsfb__empty">No categories</div>';
        } else {
          $input = $multiple ? 'checkbox' : 'radio';
          foreach ($terms as $t) {
            echo '<label class="gwsfb__opt">';
              echo '<input type="' . esc_attr($input) . '" name="cat[]" value="' . (int)$t->term_id . '">';
              echo '<span class="gwsfb__opt-text">' . esc_html($t->name) . '</span>';
            echo '</label>';
          }
        }

      echo '</div>';
    echo '</div>';
  }

  private static function filter_attributes(array $cfg, array $view = [], array $set = []): void {
    $attrs = $cfg['attributes'] ?? [];
    if (!is_array($attrs) || empty($attrs)) return;

    $style      = $cfg['style'] ?? 'checkbox';
    $hide_empty = !empty($cfg['hide_empty']);

    $attr_index = 0;

    foreach ($attrs as $tax) {
      $tax = sanitize_key($tax);
      if (!$tax || !taxonomy_exists($tax)) continue;

      $terms = get_terms([
        'taxonomy'   => $tax,
        'hide_empty' => $hide_empty,
        'orderby'    => 'name',
        'order'      => 'ASC',
      ]);

      $label = function_exists('wc_attribute_label') ? wc_attribute_label($tax) : $tax;

      $is_open = self::is_attr_block_initially_open($view, $attr_index);
      $block_classes = 'gwsfb__block ' . ($is_open ? 'is-open' : 'is-collapsed');

      echo '<div class="' . esc_attr($block_classes) . '" data-filter="attr" data-tax="' . esc_attr($tax) . '">';
        self::render_block_header((string)$label, $view, $is_open);
        echo '<div class="gwsfb__body">';

          if (is_wp_error($terms) || empty($terms)) {
            echo '<div class="gwsfb__empty">No options</div>';
          } else {
            if ($style === 'select') {
              echo '<select class="gwsfb__select gwsfb__input" name="attr[' . esc_attr($tax) . '][]">';
                echo '<option value="">Any</option>';
                foreach ($terms as $t) {
                  echo '<option value="' . (int)$t->term_id . '">' . esc_html($t->name) . '</option>';
                }
              echo '</select>';
            } else {
              foreach ($terms as $t) {
                echo '<label class="gwsfb__opt">';
                  echo '<input type="checkbox" name="attr[' . esc_attr($tax) . '][]" value="' . (int)$t->term_id . '">';
                  echo '<span class="gwsfb__opt-text">' . esc_html($t->name) . '</span>';
                echo '</label>';
              }
            }
          }

        echo '</div>';
      echo '</div>';

      $attr_index++;
    }
  }

  private static function filter_price(array $cfg, array $view = [], array $set = []): void {
    $min  = isset($cfg['min']) ? (float)$cfg['min'] : 0.0;
    $max  = isset($cfg['max']) ? (float)$cfg['max'] : 0.0;
    $step = isset($cfg['step']) && (float)$cfg['step'] > 0 ? (float)$cfg['step'] : 1.0;

    if ($step <= 0) $step = 1.0;

    $auto = self::get_auto_price_range($set);

    if (($min <= 0 && $max <= 0) || $max < $min) {
      $min = (float)$auto['min'];
      $max = (float)$auto['max'];
    } else {
      if ($min < 0) $min = 0.0;
      if ($max <= 0 && (float)$auto['max'] > 0) {
        $max = (float)$auto['max'];
      }
    }

    if ($min < 0) $min = 0.0;

    if ($max < $min) {
      $tmp = $min;
      $min = $max;
      $max = $tmp;
    }

    $min = self::round_floor_step($min, $step);
    if ($min < 0) $min = 0.0;

    $max = self::round_ceil_step($max, $step);

    if (abs($max - $min) < 0.00001) {
      $max = $min + max(1.0, $step);
    }

    $min_display = self::format_price_label($min);
    $max_display = self::format_price_label($max);

    echo '<div class="gwsfb__block is-open" data-filter="price">';
      self::render_block_header('Price', $view, true);
      echo '<div class="gwsfb__body">';

        $currency_symbol = function_exists('get_woocommerce_currency_symbol')
			  ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8')
			  : '$';

			$currency_pos = get_option('woocommerce_currency_pos', 'left');

			echo '<div class="gwsfb__price-slider gwsfb-price" data-min="' . esc_attr($min) . '" data-max="' . esc_attr($max) . '" data-step="' . esc_attr($step) . '" data-currency-symbol="' . esc_attr($currency_symbol) . '" data-currency-pos="' . esc_attr($currency_pos) . '">';

          echo '<div class="gwsfb-price__range">';
            echo '<input class="gwsfb__price-range-min gwsfb-price__input" type="range" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" value="' . esc_attr($min) . '">';
            echo '<input class="gwsfb__price-range-max gwsfb-price__input" type="range" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" value="' . esc_attr($max) . '">';
          echo '</div>';

          echo '<div class="gwsfb__price-values gwsfb-price__values">';
            echo '<span class="gwsfb__price-label gwsfb-price__value gwsfb__price-label--min gwsfb-price__value--min">' . esc_html($min_display) . '</span>';
            echo '<span class="gwsfb__price-label gwsfb-price__value gwsfb__price-label--current gwsfb-price__value--current">' . esc_html($min_display . ' - ' . $max_display) . '</span>';
            echo '<span class="gwsfb__price-label gwsfb-price__value gwsfb__price-label--max gwsfb-price__value--max">' . esc_html($max_display) . '</span>';
          echo '</div>';

          echo '<input type="hidden" name="min_price" value="' . esc_attr($min) . '">';
          echo '<input type="hidden" name="max_price" class="gwsfb__price-value-input" value="' . esc_attr($max) . '">';

        echo '</div>';

      echo '</div>';
    echo '</div>';
  }

  private static function normalize_stock_statuses_to_map($raw): array {
    $allowed = ['instock' => true, 'outofstock' => true, 'onbackorder' => true];
    $map = [];

    if (!is_array($raw)) return $map;

    foreach ($raw as $k => $v) {
      if (is_string($k) && $k !== '') {
        $kk = sanitize_key($k);
        $on = !empty($v);
        if ($kk && isset($allowed[$kk]) && $on) {
          $map[$kk] = true;
        }
        continue;
      }

      $vv = sanitize_key((string)$v);
      if ($vv && isset($allowed[$vv])) {
        $map[$vv] = true;
      }
    }

    return $map;
  }

  private static function filter_stock(array $cfg, array $view = [], array $set = []): void {
    $statuses = $cfg['statuses'] ?? [];
    $map = self::normalize_stock_statuses_to_map($statuses);

    if (empty($map)) {
      $map = [
        'instock'     => true,
        'outofstock'  => true,
        'onbackorder' => true,
      ];
    }

    echo '<div class="gwsfb__block is-open" data-filter="stock">';
      self::render_block_header('Stock', $view, true);
      echo '<div class="gwsfb__body">';

        $opts = [
          'instock'     => 'In stock',
          'outofstock'  => 'Out of stock',
          'onbackorder' => 'On backorder',
        ];

        foreach ($opts as $k => $label) {
          $checked = !empty($map[$k]) ? ' checked' : '';
          echo '<label class="gwsfb__opt">';
            echo '<input type="checkbox" name="stock_statuses[]" value="' . esc_attr($k) . '"' . $checked . '>';
            echo '<span class="gwsfb__opt-text">' . esc_html($label) . '</span>';
          echo '</label>';
        }

      echo '</div>';
    echo '</div>';
  }
}