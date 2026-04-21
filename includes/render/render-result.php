<?php
if (!defined('ABSPATH')) exit;

trait GWSFB_Render_Shell {

  private static $assets_enqueued = false;

  public static function enqueue_assets(): void {
    if (self::$assets_enqueued) return;
    self::$assets_enqueued = true;

    if (wp_style_is('gwsfb-front', 'registered')) {
      wp_enqueue_style('gwsfb-front');
    } else {
      wp_enqueue_style('gwsfb-front');
    }

    if (wp_script_is('gwsfb-config', 'registered'))  wp_enqueue_script('gwsfb-config');
    if (wp_script_is('gwsfb-filter', 'registered'))  wp_enqueue_script('gwsfb-filter');
    if (wp_script_is('gwsfb-sort', 'registered'))    wp_enqueue_script('gwsfb-sort');
    if (wp_script_is('gwsfb-results', 'registered')) wp_enqueue_script('gwsfb-results');

    if (wp_script_is('gwsfb-config', 'enqueued')) {
      wp_localize_script('gwsfb-config', 'GWSFB', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gwsfb_filter'),
      ]);
    }
  }

  private static function output_critical_inline_css(string $uid): void {
    $uid = preg_replace('/[^a-zA-Z0-9\_\-]/', '', (string)$uid);
    if (!$uid) return;

    echo '<style id="' . esc_attr($uid) . '_gwsfb_critical_css">';
      echo '#' . esc_attr($uid) . '{position:relative;}';
      echo '#' . esc_attr($uid) . ' > .gwsfb__loading{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;opacity:0;z-index:50;background:var(--gwsfb-loading-bg,rgba(255,255,255,.8));}';
      echo '#' . esc_attr($uid) . '.gwsfb-is-loading > .gwsfb__loading{opacity:var(--gwsfb-loading-opacity,1);pointer-events:auto;}';

      echo '#' . esc_attr($uid) . ' .gwsfb-results__outer{position:relative;}';
      echo '#' . esc_attr($uid) . ' .gwsfb__loading-inner{min-width:80px;min-height:0;border-radius:12px;display:flex;align-items:center;justify-content:center;}';
      echo '#' . esc_attr($uid) . ' .gwsfb__spinner{width:34px;height:34px;border-radius:999px;border:3px solid rgba(0,0,0,.15);border-top-color:var(--gwsfb-loading-fg,rgba(0,0,0,.55));animation:gwsfbSpin .8s linear infinite;}';
      echo '#' . esc_attr($uid) . ' .gwsfb__spinner-icon{display:inline-flex;align-items:center;justify-content:center;line-height:0;color:var(--gwsfb-loading-fg,rgba(0,0,0,.55));}';
      echo '#' . esc_attr($uid) . ' .gwsfb__spinner-icon svg{display:block;width:100%;height:100%;fill:currentColor;stroke:currentColor;}';
      echo '#' . esc_attr($uid) . ' .gwsfb__spinner-icon.is-spinning{animation:gwsfbSpin .8s linear infinite;}';
      echo '@keyframes gwsfbSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}';
      echo '#' . esc_attr($uid) . '.gwsfb-loading-shape-square .gwsfb__spinner{border-radius:0;}';
      echo '#' . esc_attr($uid) . '.gwsfb-loading-shape-rounded .gwsfb__spinner{border-radius:8px;}';
    echo '</style>';
  }

  private static function output_inline_boot_script(string $uid): void {
    $uid = preg_replace('/[^a-zA-Z0-9\_\-]/', '', (string)$uid);
    if (!$uid) return;

    echo '<script id="' . esc_attr($uid) . '_gwsfb_boot">';
      echo '(function(){';
        echo 'var el=document.getElementById(' . wp_json_encode($uid) . ');';
        echo 'if(!el) return;';
        echo 'function done(){ el.classList.remove("gwsfb-is-loading"); }';
        echo 'if(document.readyState==="complete"){ done(); return; }';
        echo 'window.addEventListener("load", done, {once:true});';
      echo '})();';
    echo '</script>';
  }

  private static function render_loading_markup(array $view): string {
    $shape = isset($view['loading_shape']) ? sanitize_key((string)$view['loading_shape']) : 'circle';
    if (!in_array($shape, ['circle', 'square', 'rounded', 'custom'], true)) {
      $shape = 'circle';
    }

    if (
      $shape === 'custom' &&
      !empty($view['loading_icon']) &&
      is_array($view['loading_icon']) &&
      class_exists('\Elementor\Icons_Manager')
    ) {
      ob_start();
      try {
        \Elementor\Icons_Manager::render_icon($view['loading_icon'], ['aria-hidden' => 'true']);
      } catch (\Throwable $e) {
      }
      $icon_html = trim((string)ob_get_clean());

      if ($icon_html !== '') {
        if (method_exists(__CLASS__, 'normalize_icon_markup')) {
          $icon_html = self::normalize_icon_markup($icon_html);
        }
        $spin_class = !empty($view['loading_icon_spin']) ? ' is-spinning' : '';
        return '<span class="gwsfb__spinner-icon' . esc_attr($spin_class) . '">' . $icon_html . '</span>';
      }
    }

    return '<div class="gwsfb__spinner"></div>';
  }

  private static function sanitize_choice_value($value, array $allowed, string $fallback): string {
    $value = sanitize_key((string)$value);
    return in_array($value, $allowed, true) ? $value : $fallback;
  }

  private static function sanitize_bool_value($value, int $fallback = 0): int {
    if ($value === null || $value === '') {
      return $fallback ? 1 : 0;
    }
    return !empty($value) ? 1 : 0;
  }

  private static function pick_layout_choice(array $layout, string $base, string $device, string $fallback, array $allowed): string {
    $key = $base . '_' . $device;
    if (array_key_exists($key, $layout) && $layout[$key] !== '' && $layout[$key] !== null) {
      return self::sanitize_choice_value($layout[$key], $allowed, $fallback);
    }
    if (array_key_exists($base, $layout) && $layout[$base] !== '' && $layout[$base] !== null) {
      return self::sanitize_choice_value($layout[$base], $allowed, $fallback);
    }
    return $fallback;
  }

  private static function pick_layout_number(array $layout, string $base, string $device, float $fallback, float $min, float $max): float {
    $key = $base . '_' . $device;
    $value = null;

    if (array_key_exists($key, $layout) && $layout[$key] !== '' && $layout[$key] !== null) {
      $value = $layout[$key];
    } elseif (array_key_exists($base, $layout) && $layout[$base] !== '' && $layout[$base] !== null) {
      $value = $layout[$base];
    }

    if ($value === null || $value === '') {
      $value = $fallback;
    }

    $value = (float)$value;
    if ($value < $min) $value = $min;
    if ($value > $max) $value = $max;

    return $value;
  }

  private static function pick_layout_bool(array $layout, string $base, string $device, int $fallback = 0): int {
    $key = $base . '_' . $device;
    if (array_key_exists($key, $layout)) {
      return self::sanitize_bool_value($layout[$key], $fallback);
    }
    if (array_key_exists($base, $layout)) {
      return self::sanitize_bool_value($layout[$base], $fallback);
    }
    return $fallback ? 1 : 0;
  }

  public static function shortcode($atts) {
    $atts = shortcode_atts([
      'id'      => '',
      'group'   => '',
      'layout'  => 'left',
      'columns' => '4',
    ], $atts, 'gws_filters');

    $id      = sanitize_text_field($atts['id']);
    $group   = sanitize_key($atts['group']);
    $layout  = in_array($atts['layout'], ['left', 'top'], true) ? $atts['layout'] : 'left';
    $columns = max(1, min(6, (int)$atts['columns']));

    return self::render_widget($id, [
      'mode'    => 'both',
      'group'   => $group,
      'layout'  => $layout,
      'columns' => $columns,
    ]);
  }

  public static function shortcode_filters_only($atts) {
    $atts = shortcode_atts([
      'id'    => '',
      'group' => '',
    ], $atts, 'gws_filters_panel');

    $id    = sanitize_text_field($atts['id']);
    $group = sanitize_key($atts['group']);

    return self::render_filters_only($id, $group, []);
  }

  public static function shortcode_results_only($atts) {
    $atts = shortcode_atts([
      'id'      => '',
      'group'   => '',
      'columns' => '4',
    ], $atts, 'gws_products_results');

    $id      = sanitize_text_field($atts['id']);
    $group   = sanitize_key($atts['group']);
    $columns = max(1, min(6, (int)$atts['columns']));

    return self::render_results_only($id, $group, $columns, []);
  }

  public static function render_widget($id, array $args = []): string {
    $id = sanitize_text_field($id);
    if (!$id) return '';

    $set = GWSFB_Helpers::get_set($id);
    if (!$set || empty($set['enabled'])) {
      return '<div class="gwsfb-error" style="color:#b32d2e;">GWS: Filter Set not found or disabled.</div>';
    }

    if (!class_exists('WooCommerce')) {
      return '<div class="gwsfb-error" style="color:#b32d2e;">GWS: WooCommerce not active.</div>';
    }

    self::enqueue_assets();

    $mode = isset($args['mode']) && in_array($args['mode'], ['both', 'filters', 'results'], true)
      ? $args['mode']
      : 'both';

    $group = isset($args['group']) ? sanitize_key($args['group']) : '';
    if ($group === '') $group = 'g' . $id;

    $layout = isset($args['layout']) && in_array($args['layout'], ['left', 'top'], true)
      ? $args['layout']
      : 'left';

    $cols = isset($args['columns']) && (int)$args['columns'] > 0
      ? max(1, min(6, (int)$args['columns']))
      : (int)($set['results']['columns'] ?? 4);

    $uid = 'gwsfb_' . esc_attr($id) . '_' . wp_rand(100, 999);

    $default_view = [
      'layout'                              => 'small_grid',
      'columns'                             => $cols,
      'rows'                                => 0,
      'columns_desktop'                     => $cols,
      'columns_tablet'                      => max(1, min(6, $cols)),
      'columns_mobile'                      => max(1, min(6, $cols)),
      'rows_desktop'                        => 0,
      'rows_tablet'                         => 0,
      'rows_mobile'                         => 0,
      'columns_small_grid'                  => $cols,
      'columns_large_grid'                  => $cols,
      'columns_list'                        => 1,
      'show_title'                          => 1,
      'show_title_desktop'                  => 1,
      'show_title_tablet'                   => 1,
      'show_title_mobile'                   => 1,
      'show_price'                          => 1,
      'show_price_desktop'                  => 1,
      'show_price_tablet'                   => 1,
      'show_price_mobile'                   => 1,
      'show_rating'                         => 1,
      'show_rating_desktop'                 => 1,
      'show_rating_tablet'                  => 1,
      'show_rating_mobile'                   => 1,
      'show_add_to_cart'                    => 1,
      'show_add_to_cart_desktop'            => 1,
      'show_add_to_cart_tablet'             => 1,
      'show_add_to_cart_mobile'             => 1,
      'show_description'                    => 0,
      'show_description_desktop'            => 0,
      'show_description_tablet'             => 0,
      'show_description_mobile'             => 0,
      'show_view_more'                      => 1,
      'show_view_more_desktop'              => 1,
      'show_view_more_tablet'               => 1,
      'show_view_more_mobile'               => 1,
      'small_options_enable'                => 1,
      'small_options_open'                  => 0,
      'options_label'                       => 'Options',
      'view_more_label'                     => 'View more',
      'sort_enabled'                        => 0,
      'sort_default'                        => '',
      'sort_label_text'                     => 'Sort',
      'sort_label_show'                     => 1,
      'sort_summary_layout'                 => 'summary_left_sort_right',
      'show_summary'                        => 1,
      'loading_bg'                          => '',
      'loading_fg'                          => '',
      'loading_opacity'                     => '',
      'loading_shape'                       => 'circle',
      'loading_icon'                        => [],
      'loading_icon_spin'                   => 1,
      'image_placeholder_mode'              => 'none',
      'image_placeholder_text'              => 'Loading image',
      'image_placeholder_image'             => ['id' => 0, 'url' => ''],
      'layout_switcher_enable'              => 1,
      'layout_small_grid'                   => 1,
      'layout_large_grid'                   => 1,
      'layout_list'                         => 1,
      'list_mobile_layout'                  => 'row',
      'list_mobile_layout_desktop'          => 'row',
      'list_mobile_layout_tablet'           => 'row',
      'list_mobile_layout_mobile'           => 'column',
      'list_mobile_image_width'             => 28,
      'list_mobile_image_width_desktop'     => 28,
      'list_mobile_image_width_tablet'      => 34,
      'list_mobile_image_width_mobile'      => 40,
      'list_mobile_vertical_align'          => 'flex-start',
      'list_mobile_vertical_align_desktop'  => 'flex-start',
      'list_mobile_vertical_align_tablet'   => 'flex-start',
      'list_mobile_vertical_align_mobile'   => 'flex-start',
      'title_align'                         => 'left',
      'title_align_desktop'                 => 'left',
      'title_align_tablet'                  => 'left',
      'title_align_mobile'                  => 'left',
      'desc_align'                          => 'left',
      'desc_align_desktop'                  => 'left',
      'desc_align_tablet'                   => 'left',
      'desc_align_mobile'                   => 'left',
      'price_align'                         => 'left',
      'price_align_desktop'                 => 'left',
      'price_align_tablet'                  => 'left',
      'price_align_mobile'                  => 'left',
      'more_align'                          => 'left',
      'more_align_desktop'                  => 'left',
      'more_align_tablet'                   => 'left',
      'more_align_mobile'                   => 'left',
      'card_justify_content'                => '',
      'card_justify_content_desktop'        => '',
      'card_justify_content_tablet'         => '',
      'card_justify_content_mobile'         => '',
      'responsive_device'                   => 'desktop',
    ];

    $root_classes = ['gwsfb'];

    if ($mode === 'both' || $mode === 'results') {
      $root_classes[] = 'gwsfb-results';
      $root_classes[] = 'gwsfb-has-loading';
      $root_classes[] = 'gwsfb-is-loading';
      $root_classes[] = 'gwsfb-loading-shape-circle';
    }

    $data_view_attr = '';
    if ($mode === 'both' || $mode === 'results') {
      $data_view_attr = ' data-view="' . esc_attr(wp_json_encode($default_view)) . '"';
    }

    $orderby0 = method_exists(__CLASS__, 'get_default_orderby_from_set') ? self::get_default_orderby_from_set($set) : '';
    if (!$orderby0) $orderby0 = 'menu_order';

    ob_start();

    echo '<div class="' . esc_attr(implode(' ', $root_classes)) . '" id="' . esc_attr($uid) . '" data-set-id="' . esc_attr($id) . '" data-group="' . esc_attr($group) . '" data-layout="' . esc_attr($layout) . '" data-mode="' . esc_attr($mode) . '"' . $data_view_attr . '>';

      if ($mode === 'both' || $mode === 'filters') {
        echo '<div class="gwsfb-filters__outer">';

          echo '<div class="gwsfb__loading" aria-hidden="true">';
            echo '<div class="gwsfb__loading-inner">';
              echo self::render_loading_markup($default_view);
            echo '</div>';
          echo '</div>';

          echo '<div class="gwsfb__filters gwsfb-filters__panel">';
            self::render_filters($set, []);
          echo '</div>';

        echo '</div>';
      }

      if ($mode === 'both' || $mode === 'results') {
        self::output_critical_inline_css($uid);
        self::output_inline_boot_script($uid);

        echo '<div class="gwsfb__loading" aria-hidden="true">';
          echo '<div class="gwsfb__loading-inner">';
            echo self::render_loading_markup($default_view);
          echo '</div>';
        echo '</div>';

        echo '<div class="gwsfb-results__outer">';
          echo '<div class="gwsfb__results">';
            self::render_results($set, ['page' => 1, 'orderby' => $orderby0, 'group' => $group], $default_view);
          echo '</div>';
        echo '</div>';
      }

    echo '</div>';

    return ob_get_clean();
  }

  public static function render_filters_only($id, $group = '', array $view = []): string {
    $id = sanitize_text_field($id);
    if (!$id) return '';

    $set = GWSFB_Helpers::get_set($id);
    if (!$set || empty($set['enabled'])) {
      return '<div class="gwsfb-error" style="color:#b32d2e;">GWS: Filter Set not found or disabled.</div>';
    }

    if (!class_exists('WooCommerce')) {
      return '<div class="gwsfb-error" style="color:#b32d2e;">GWS: WooCommerce not active.</div>';
    }

    self::enqueue_assets();

    $group = sanitize_key((string)$group);
    if ($group === '') $group = 'g' . $id;

    $uid = 'gwsfb_filters_' . esc_attr($id) . '_' . wp_rand(100, 999);

    $shape = isset($view['loading_shape']) ? sanitize_key((string)$view['loading_shape']) : 'circle';
    if (!in_array($shape, ['circle', 'square', 'rounded', 'custom'], true)) $shape = 'circle';

    $style_vars = [];
    if (!empty($view['loading_bg'])) $style_vars[] = '--gwsfb-loading-bg:' . $view['loading_bg'];
    if (!empty($view['loading_fg'])) $style_vars[] = '--gwsfb-loading-fg:' . $view['loading_fg'];
    if (isset($view['loading_opacity']) && $view['loading_opacity'] !== '') {
      $opacity = max(0, min(1, (float)$view['loading_opacity']));
      $style_vars[] = '--gwsfb-loading-opacity:' . $opacity;
    }
    $style_attr = $style_vars ? ' style="' . esc_attr(implode(';', $style_vars)) . '"' : '';

    $root_classes = [
      'gwsfb',
      'gwsfb-filters',
      'gwsfb-has-loading',
      'gwsfb-is-loading',
      'gwsfb-loading-shape-' . $shape,
    ];

    ob_start();

    echo '<div class="' . esc_attr(implode(' ', $root_classes)) . '" id="' . esc_attr($uid) . '" data-set-id="' . esc_attr($id) . '" data-group="' . esc_attr($group) . '" data-mode="filters"' . $style_attr . '>';

      self::output_critical_inline_css($uid);
      self::output_inline_boot_script($uid);

      echo '<div class="gwsfb__loading" aria-hidden="true">';
        echo '<div class="gwsfb__loading-inner">';
          echo self::render_loading_markup($view);
        echo '</div>';
      echo '</div>';

      echo '<div class="gwsfb-filters__outer">';
        echo '<div class="gwsfb__filters gwsfb-filters__panel">';
          self::render_filters($set, $view);
        echo '</div>';
      echo '</div>';

    echo '</div>';

    return ob_get_clean();
  }

  public static function render_results_only($id, $group = '', $cols = 4, array $view = []): string {
    $id = sanitize_text_field($id);
    if (!$id) return '';

    $set = GWSFB_Helpers::get_set($id);
    if (!$set || empty($set['enabled'])) {
      return '<div class="gwsfb-error" style="color:#b32d2e;">GWS: Filter Set not found or disabled.</div>';
    }

    if (!class_exists('WooCommerce')) {
      return '<div class="gwsfb-error" style="color:#b32d2e;">GWS: WooCommerce not active.</div>';
    }

    self::enqueue_assets();

    $group = sanitize_key((string)$group);
    if ($group === '') $group = 'g' . $id;

    $cols = max(1, min(6, (int)$cols));

    $view = self::sanitize_view($view);

    $current_orderby = '';
    if (!empty($view['sort_default'])) $current_orderby = sanitize_key($view['sort_default']);
    if (!$current_orderby) $current_orderby = self::get_default_orderby_from_set($set);
    if (!$current_orderby) $current_orderby = 'menu_order';

    $uid       = 'gwsfb_results_' . esc_attr($id) . '_' . wp_rand(100, 999);
    $data_view = esc_attr(wp_json_encode($view));

    $shape = isset($view['loading_shape']) ? sanitize_key((string)$view['loading_shape']) : 'circle';
    if (!in_array($shape, ['circle', 'square', 'rounded', 'custom'], true)) $shape = 'circle';

    $root_classes = [
      'gwsfb',
      'gwsfb-results',
      'gwsfb-has-loading',
      'gwsfb-is-loading',
      'gwsfb-loading-shape-' . $shape,
    ];

    $style_vars = [];
    if (!empty($view['loading_bg'])) $style_vars[] = '--gwsfb-loading-bg:' . $view['loading_bg'];
    if (!empty($view['loading_fg'])) $style_vars[] = '--gwsfb-loading-fg:' . $view['loading_fg'];
    if (isset($view['loading_opacity']) && $view['loading_opacity'] !== '') {
      $opacity = max(0, min(1, (float)$view['loading_opacity']));
      $style_vars[] = '--gwsfb-loading-opacity:' . $opacity;
    }
    $style_attr = $style_vars ? ' style="' . esc_attr(implode(';', $style_vars)) . '"' : '';

    ob_start();

    echo '<div class="' . esc_attr(implode(' ', $root_classes)) . '" id="' . esc_attr($uid) . '" data-set-id="' . esc_attr($id) . '" data-group="' . esc_attr($group) . '" data-mode="results" data-view="' . $data_view . '"' . $style_attr . '>';

      self::output_critical_inline_css($uid);
      self::output_inline_boot_script($uid);

      echo '<div class="gwsfb__loading" aria-hidden="true">';
        echo '<div class="gwsfb__loading-inner">';
          echo self::render_loading_markup($view);
        echo '</div>';
      echo '</div>';

      echo '<div class="gwsfb-results__outer">';
        echo '<div class="gwsfb__results">';
          self::render_results($set, ['page' => 1, 'orderby' => $current_orderby, 'group' => $group], $view);
        echo '</div>';
      echo '</div>';

    echo '</div>';

    return ob_get_clean();
  }

  public static function sanitize_view(array $view): array {
    $out = $view;

    $layouts = [];
    if (!empty($out['layouts']) && is_array($out['layouts'])) {
      foreach ($out['layouts'] as $m) {
        if (!is_array($m)) continue;

        $id = isset($m['id']) ? preg_replace('/[^a-zA-Z0-9\_\-]/', '', (string)$m['id']) : '';
        if ($id === '') continue;

        $type = isset($m['type']) ? sanitize_key((string)$m['type']) : 'grid';
        if (!in_array($type, ['grid', 'list'], true)) $type = 'grid';

        $label = isset($m['label']) ? sanitize_text_field((string)$m['label']) : '';
        $icon  = (isset($m['icon']) && is_array($m['icon'])) ? $m['icon'] : [];

        $cols_desktop = isset($m['columns_desktop']) ? (int)$m['columns_desktop'] : (isset($m['columns']) ? (int)$m['columns'] : 4);
        $cols_tablet  = isset($m['columns_tablet']) ? (int)$m['columns_tablet'] : $cols_desktop;
        $cols_mobile  = isset($m['columns_mobile']) ? (int)$m['columns_mobile'] : $cols_tablet;

        $cols_desktop = max(1, min(6, $cols_desktop));
        $cols_tablet  = max(1, min(6, $cols_tablet));
        $cols_mobile  = max(1, min(6, $cols_mobile));

        if ($type === 'list') {
          $cols_desktop = 1;
          $cols_tablet  = 1;
          $cols_mobile  = 1;
        }

        $rows_desktop = isset($m['rows_desktop']) ? max(0, (int)$m['rows_desktop']) : (isset($m['rows']) ? max(0, (int)$m['rows']) : 0);
        $rows_tablet  = isset($m['rows_tablet']) ? max(0, (int)$m['rows_tablet']) : $rows_desktop;
        $rows_mobile  = isset($m['rows_mobile']) ? max(0, (int)$m['rows_mobile']) : $rows_tablet;

        $show_title_desktop = self::sanitize_bool_value($m['show_title_desktop'] ?? ($m['show_title'] ?? 1), 1);
        $show_title_tablet  = self::sanitize_bool_value($m['show_title_tablet'] ?? $show_title_desktop, $show_title_desktop);
        $show_title_mobile  = self::sanitize_bool_value($m['show_title_mobile'] ?? $show_title_tablet, $show_title_tablet);

        $show_price_desktop = self::sanitize_bool_value($m['show_price_desktop'] ?? ($m['show_price'] ?? 1), 1);
        $show_price_tablet  = self::sanitize_bool_value($m['show_price_tablet'] ?? $show_price_desktop, $show_price_desktop);
        $show_price_mobile  = self::sanitize_bool_value($m['show_price_mobile'] ?? $show_price_tablet, $show_price_tablet);

        $show_rating_desktop = self::sanitize_bool_value($m['show_rating_desktop'] ?? ($m['show_rating'] ?? 1), 1);
        $show_rating_tablet  = self::sanitize_bool_value($m['show_rating_tablet'] ?? $show_rating_desktop, $show_rating_desktop);
        $show_rating_mobile  = self::sanitize_bool_value($m['show_rating_mobile'] ?? $show_rating_tablet, $show_rating_tablet);

        $show_add_to_cart_desktop = self::sanitize_bool_value($m['show_add_to_cart_desktop'] ?? ($m['show_add_to_cart'] ?? 1), 1);
        $show_add_to_cart_tablet  = self::sanitize_bool_value($m['show_add_to_cart_tablet'] ?? $show_add_to_cart_desktop, $show_add_to_cart_desktop);
        $show_add_to_cart_mobile  = self::sanitize_bool_value($m['show_add_to_cart_mobile'] ?? $show_add_to_cart_tablet, $show_add_to_cart_tablet);

        $show_description_desktop = self::sanitize_bool_value($m['show_description_desktop'] ?? ($m['show_description'] ?? 0), 0);
        $show_description_tablet  = self::sanitize_bool_value($m['show_description_tablet'] ?? $show_description_desktop, $show_description_desktop);
        $show_description_mobile  = self::sanitize_bool_value($m['show_description_mobile'] ?? $show_description_tablet, $show_description_tablet);

        $show_view_more_desktop = self::sanitize_bool_value($m['show_view_more_desktop'] ?? ($m['show_view_more'] ?? 1), 1);
        $show_view_more_tablet  = self::sanitize_bool_value($m['show_view_more_tablet'] ?? $show_view_more_desktop, $show_view_more_desktop);
        $show_view_more_mobile  = self::sanitize_bool_value($m['show_view_more_mobile'] ?? $show_view_more_tablet, $show_view_more_tablet);

        $list_mobile_layout_desktop = self::sanitize_choice_value($m['list_mobile_layout_desktop'] ?? ($m['list_mobile_layout'] ?? 'row'), ['row', 'column'], 'row');
        $list_mobile_layout_tablet  = self::sanitize_choice_value($m['list_mobile_layout_tablet'] ?? $list_mobile_layout_desktop, ['row', 'column'], $list_mobile_layout_desktop);
        $list_mobile_layout_mobile  = self::sanitize_choice_value($m['list_mobile_layout_mobile'] ?? $list_mobile_layout_tablet, ['row', 'column'], $list_mobile_layout_tablet);

        $list_mobile_image_width_desktop = isset($m['list_mobile_image_width_desktop']) ? (float)$m['list_mobile_image_width_desktop'] : (isset($m['list_mobile_image_width']) ? (float)$m['list_mobile_image_width'] : 28);
        $list_mobile_image_width_tablet  = isset($m['list_mobile_image_width_tablet']) ? (float)$m['list_mobile_image_width_tablet'] : $list_mobile_image_width_desktop;
        $list_mobile_image_width_mobile  = isset($m['list_mobile_image_width_mobile']) ? (float)$m['list_mobile_image_width_mobile'] : $list_mobile_image_width_tablet;

        $list_mobile_image_width_desktop = max(15, min(80, $list_mobile_image_width_desktop));
        $list_mobile_image_width_tablet  = max(15, min(80, $list_mobile_image_width_tablet));
        $list_mobile_image_width_mobile  = max(15, min(80, $list_mobile_image_width_mobile));

        $list_mobile_vertical_align_desktop = self::sanitize_choice_value($m['list_mobile_vertical_align_desktop'] ?? ($m['list_mobile_vertical_align'] ?? 'flex-start'), ['flex-start', 'center', 'flex-end'], 'flex-start');
        $list_mobile_vertical_align_tablet  = self::sanitize_choice_value($m['list_mobile_vertical_align_tablet'] ?? $list_mobile_vertical_align_desktop, ['flex-start', 'center', 'flex-end'], $list_mobile_vertical_align_desktop);
        $list_mobile_vertical_align_mobile  = self::sanitize_choice_value($m['list_mobile_vertical_align_mobile'] ?? $list_mobile_vertical_align_tablet, ['flex-start', 'center', 'flex-end'], $list_mobile_vertical_align_tablet);

        $title_align_desktop = self::sanitize_choice_value($m['title_align_desktop'] ?? ($m['title_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
        $title_align_tablet  = self::sanitize_choice_value($m['title_align_tablet'] ?? $title_align_desktop, ['left', 'center', 'right'], $title_align_desktop);
        $title_align_mobile  = self::sanitize_choice_value($m['title_align_mobile'] ?? $title_align_tablet, ['left', 'center', 'right'], $title_align_tablet);

        $desc_align_desktop = self::sanitize_choice_value($m['desc_align_desktop'] ?? ($m['desc_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
        $desc_align_tablet  = self::sanitize_choice_value($m['desc_align_tablet'] ?? $desc_align_desktop, ['left', 'center', 'right'], $desc_align_desktop);
        $desc_align_mobile  = self::sanitize_choice_value($m['desc_align_mobile'] ?? $desc_align_tablet, ['left', 'center', 'right'], $desc_align_tablet);

        $price_align_desktop = self::sanitize_choice_value($m['price_align_desktop'] ?? ($m['price_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
        $price_align_tablet  = self::sanitize_choice_value($m['price_align_tablet'] ?? $price_align_desktop, ['left', 'center', 'right'], $price_align_desktop);
        $price_align_mobile  = self::sanitize_choice_value($m['price_align_mobile'] ?? $price_align_tablet, ['left', 'center', 'right'], $price_align_tablet);

        $more_align_desktop = self::sanitize_choice_value($m['more_align_desktop'] ?? ($m['more_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
        $more_align_tablet  = self::sanitize_choice_value($m['more_align_tablet'] ?? $more_align_desktop, ['left', 'center', 'right'], $more_align_desktop);
        $more_align_mobile  = self::sanitize_choice_value($m['more_align_mobile'] ?? $more_align_tablet, ['left', 'center', 'right'], $more_align_tablet);

        $card_justify_content_desktop = self::sanitize_choice_value($m['card_justify_content_desktop'] ?? ($m['card_justify_content'] ?? ''), ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], '');
        $card_justify_content_tablet  = self::sanitize_choice_value($m['card_justify_content_tablet'] ?? $card_justify_content_desktop, ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], $card_justify_content_desktop);
        $card_justify_content_mobile  = self::sanitize_choice_value($m['card_justify_content_mobile'] ?? $card_justify_content_tablet, ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], $card_justify_content_tablet);

        $layouts[] = [
          'id'                                  => $id,
          'type'                                => $type,
          'label'                               => $label,
          'icon'                                => $icon,
          'columns'                             => $cols_desktop,
          'rows'                                => $rows_desktop,
          'columns_desktop'                     => $cols_desktop,
          'columns_tablet'                      => $cols_tablet,
          'columns_mobile'                      => $cols_mobile,
          'rows_desktop'                        => $rows_desktop,
          'rows_tablet'                         => $rows_tablet,
          'rows_mobile'                         => $rows_mobile,

          'show_title'                          => $show_title_desktop,
          'show_title_desktop'                  => $show_title_desktop,
          'show_title_tablet'                   => $show_title_tablet,
          'show_title_mobile'                   => $show_title_mobile,

          'show_price'                          => $show_price_desktop,
          'show_price_desktop'                  => $show_price_desktop,
          'show_price_tablet'                   => $show_price_tablet,
          'show_price_mobile'                   => $show_price_mobile,

          'show_rating'                         => $show_rating_desktop,
          'show_rating_desktop'                 => $show_rating_desktop,
          'show_rating_tablet'                  => $show_rating_tablet,
          'show_rating_mobile'                  => $show_rating_mobile,

          'show_add_to_cart'                    => $show_add_to_cart_desktop,
          'show_add_to_cart_desktop'            => $show_add_to_cart_desktop,
          'show_add_to_cart_tablet'             => $show_add_to_cart_tablet,
          'show_add_to_cart_mobile'             => $show_add_to_cart_mobile,

          'show_description'                    => $show_description_desktop,
          'show_description_desktop'            => $show_description_desktop,
          'show_description_tablet'             => $show_description_tablet,
          'show_description_mobile'             => $show_description_mobile,

          'show_view_more'                      => $show_view_more_desktop,
          'show_view_more_desktop'              => $show_view_more_desktop,
          'show_view_more_tablet'               => $show_view_more_tablet,
          'show_view_more_mobile'               => $show_view_more_mobile,

          'view_more_label'                     => (isset($m['view_more_label']) && $m['view_more_label'] !== '') ? sanitize_text_field((string)$m['view_more_label']) : 'View more',
          'options_enable'                      => (!empty($m['options_enable']) && $type === 'grid') ? 1 : 0,
          'options_open'                        => !empty($m['options_open']) ? 1 : 0,
          'options_label'                       => (isset($m['options_label']) && $m['options_label'] !== '') ? sanitize_text_field((string)$m['options_label']) : 'Options',

          'list_mobile_layout'                  => $list_mobile_layout_desktop,
          'list_mobile_layout_desktop'          => $list_mobile_layout_desktop,
          'list_mobile_layout_tablet'           => $list_mobile_layout_tablet,
          'list_mobile_layout_mobile'           => $list_mobile_layout_mobile,

          'list_mobile_image_width'             => $list_mobile_image_width_desktop,
          'list_mobile_image_width_desktop'     => $list_mobile_image_width_desktop,
          'list_mobile_image_width_tablet'      => $list_mobile_image_width_tablet,
          'list_mobile_image_width_mobile'      => $list_mobile_image_width_mobile,

          'list_mobile_vertical_align'          => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_desktop'  => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_tablet'   => $list_mobile_vertical_align_tablet,
          'list_mobile_vertical_align_mobile'   => $list_mobile_vertical_align_mobile,

          'title_align'                         => $title_align_desktop,
          'title_align_desktop'                 => $title_align_desktop,
          'title_align_tablet'                  => $title_align_tablet,
          'title_align_mobile'                  => $title_align_mobile,

          'desc_align'                          => $desc_align_desktop,
          'desc_align_desktop'                  => $desc_align_desktop,
          'desc_align_tablet'                   => $desc_align_tablet,
          'desc_align_mobile'                   => $desc_align_mobile,

          'price_align'                         => $price_align_desktop,
          'price_align_desktop'                 => $price_align_desktop,
          'price_align_tablet'                  => $price_align_tablet,
          'price_align_mobile'                  => $price_align_mobile,

          'more_align'                          => $more_align_desktop,
          'more_align_desktop'                  => $more_align_desktop,
          'more_align_tablet'                   => $more_align_tablet,
          'more_align_mobile'                   => $more_align_mobile,

          'card_justify_content'                => $card_justify_content_desktop,
          'card_justify_content_desktop'        => $card_justify_content_desktop,
          'card_justify_content_tablet'         => $card_justify_content_tablet,
          'card_justify_content_mobile'         => $card_justify_content_mobile,
        ];
      }
    }

    if (empty($layouts)) {
      $layout  = isset($out['layout']) ? sanitize_key((string)$out['layout']) : 'small_grid';
      if (!in_array($layout, ['small_grid', 'large_grid', 'list'], true)) $layout = 'small_grid';

      $base_cols = isset($out['columns']) ? (int)$out['columns'] : 4;
      $base_cols = max(1, min(6, $base_cols));

      $cols_small = isset($out['columns_small_grid']) ? (int)$out['columns_small_grid'] : $base_cols;
      $cols_large = isset($out['columns_large_grid']) ? (int)$out['columns_large_grid'] : $base_cols;

      $cols_small = max(1, min(6, $cols_small));
      $cols_large = max(1, min(6, $cols_large));

      $show_title_desktop = self::sanitize_bool_value($out['show_title_desktop'] ?? ($out['show_title'] ?? 1), 1);
      $show_title_tablet  = self::sanitize_bool_value($out['show_title_tablet'] ?? $show_title_desktop, $show_title_desktop);
      $show_title_mobile  = self::sanitize_bool_value($out['show_title_mobile'] ?? $show_title_tablet, $show_title_tablet);

      $show_price_desktop = self::sanitize_bool_value($out['show_price_desktop'] ?? ($out['show_price'] ?? 1), 1);
      $show_price_tablet  = self::sanitize_bool_value($out['show_price_tablet'] ?? $show_price_desktop, $show_price_desktop);
      $show_price_mobile  = self::sanitize_bool_value($out['show_price_mobile'] ?? $show_price_tablet, $show_price_tablet);

      $show_rating_desktop = self::sanitize_bool_value($out['show_rating_desktop'] ?? ($out['show_rating'] ?? 1), 1);
      $show_rating_tablet  = self::sanitize_bool_value($out['show_rating_tablet'] ?? $show_rating_desktop, $show_rating_desktop);
      $show_rating_mobile  = self::sanitize_bool_value($out['show_rating_mobile'] ?? $show_rating_tablet, $show_rating_tablet);

      $show_add_to_cart_desktop = self::sanitize_bool_value($out['show_add_to_cart_desktop'] ?? ($out['show_add_to_cart'] ?? 1), 1);
      $show_add_to_cart_tablet  = self::sanitize_bool_value($out['show_add_to_cart_tablet'] ?? $show_add_to_cart_desktop, $show_add_to_cart_desktop);
      $show_add_to_cart_mobile  = self::sanitize_bool_value($out['show_add_to_cart_mobile'] ?? $show_add_to_cart_tablet, $show_add_to_cart_tablet);

      $show_description_desktop = self::sanitize_bool_value($out['show_description_desktop'] ?? ($out['show_description'] ?? 0), 0);
      $show_description_tablet  = self::sanitize_bool_value($out['show_description_tablet'] ?? $show_description_desktop, $show_description_desktop);
      $show_description_mobile  = self::sanitize_bool_value($out['show_description_mobile'] ?? $show_description_tablet, $show_description_tablet);

      $show_view_more_desktop = self::sanitize_bool_value($out['show_view_more_desktop'] ?? ($out['show_view_more'] ?? 1), 1);
      $show_view_more_tablet  = self::sanitize_bool_value($out['show_view_more_tablet'] ?? $show_view_more_desktop, $show_view_more_desktop);
      $show_view_more_mobile  = self::sanitize_bool_value($out['show_view_more_mobile'] ?? $show_view_more_tablet, $show_view_more_tablet);

      $list_mobile_layout_desktop = self::sanitize_choice_value($out['list_mobile_layout_desktop'] ?? ($out['list_mobile_layout'] ?? 'row'), ['row', 'column'], 'row');
      $list_mobile_layout_tablet  = self::sanitize_choice_value($out['list_mobile_layout_tablet'] ?? $list_mobile_layout_desktop, ['row', 'column'], $list_mobile_layout_desktop);
      $list_mobile_layout_mobile  = self::sanitize_choice_value($out['list_mobile_layout_mobile'] ?? $list_mobile_layout_tablet, ['row', 'column'], $list_mobile_layout_tablet);

      $list_mobile_image_width_desktop = isset($out['list_mobile_image_width_desktop']) ? (float)$out['list_mobile_image_width_desktop'] : (isset($out['list_mobile_image_width']) ? (float)$out['list_mobile_image_width'] : 28);
      $list_mobile_image_width_tablet  = isset($out['list_mobile_image_width_tablet']) ? (float)$out['list_mobile_image_width_tablet'] : $list_mobile_image_width_desktop;
      $list_mobile_image_width_mobile  = isset($out['list_mobile_image_width_mobile']) ? (float)$out['list_mobile_image_width_mobile'] : $list_mobile_image_width_tablet;

      $list_mobile_image_width_desktop = max(15, min(80, $list_mobile_image_width_desktop));
      $list_mobile_image_width_tablet  = max(15, min(80, $list_mobile_image_width_tablet));
      $list_mobile_image_width_mobile  = max(15, min(80, $list_mobile_image_width_mobile));

      $list_mobile_vertical_align_desktop = self::sanitize_choice_value($out['list_mobile_vertical_align_desktop'] ?? ($out['list_mobile_vertical_align'] ?? 'flex-start'), ['flex-start', 'center', 'flex-end'], 'flex-start');
      $list_mobile_vertical_align_tablet  = self::sanitize_choice_value($out['list_mobile_vertical_align_tablet'] ?? $list_mobile_vertical_align_desktop, ['flex-start', 'center', 'flex-end'], $list_mobile_vertical_align_desktop);
      $list_mobile_vertical_align_mobile  = self::sanitize_choice_value($out['list_mobile_vertical_align_mobile'] ?? $list_mobile_vertical_align_tablet, ['flex-start', 'center', 'flex-end'], $list_mobile_vertical_align_tablet);

      $title_align_desktop = self::sanitize_choice_value($out['title_align_desktop'] ?? ($out['title_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
      $title_align_tablet  = self::sanitize_choice_value($out['title_align_tablet'] ?? $title_align_desktop, ['left', 'center', 'right'], $title_align_desktop);
      $title_align_mobile  = self::sanitize_choice_value($out['title_align_mobile'] ?? $title_align_tablet, ['left', 'center', 'right'], $title_align_tablet);

      $desc_align_desktop = self::sanitize_choice_value($out['desc_align_desktop'] ?? ($out['desc_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
      $desc_align_tablet  = self::sanitize_choice_value($out['desc_align_tablet'] ?? $desc_align_desktop, ['left', 'center', 'right'], $desc_align_desktop);
      $desc_align_mobile  = self::sanitize_choice_value($out['desc_align_mobile'] ?? $desc_align_tablet, ['left', 'center', 'right'], $desc_align_tablet);

      $price_align_desktop = self::sanitize_choice_value($out['price_align_desktop'] ?? ($out['price_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
      $price_align_tablet  = self::sanitize_choice_value($out['price_align_tablet'] ?? $price_align_desktop, ['left', 'center', 'right'], $price_align_desktop);
      $price_align_mobile  = self::sanitize_choice_value($out['price_align_mobile'] ?? $price_align_tablet, ['left', 'center', 'right'], $price_align_tablet);

      $more_align_desktop = self::sanitize_choice_value($out['more_align_desktop'] ?? ($out['more_align'] ?? 'left'), ['left', 'center', 'right'], 'left');
      $more_align_tablet  = self::sanitize_choice_value($out['more_align_tablet'] ?? $more_align_desktop, ['left', 'center', 'right'], $more_align_desktop);
      $more_align_mobile  = self::sanitize_choice_value($out['more_align_mobile'] ?? $more_align_tablet, ['left', 'center', 'right'], $more_align_tablet);

      $card_justify_content_desktop = self::sanitize_choice_value($out['card_justify_content_desktop'] ?? ($out['card_justify_content'] ?? ''), ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], '');
      $card_justify_content_tablet  = self::sanitize_choice_value($out['card_justify_content_tablet'] ?? $card_justify_content_desktop, ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], $card_justify_content_desktop);
      $card_justify_content_mobile  = self::sanitize_choice_value($out['card_justify_content_mobile'] ?? $card_justify_content_tablet, ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], $card_justify_content_tablet);

      $layouts = [
        [
          'id'                                  => 'small_grid',
          'type'                                => 'grid',
          'label'                               => 'Grid',
          'icon'                                => [],
          'columns'                             => $cols_small,
          'rows'                                => 0,
          'columns_desktop'                     => $cols_small,
          'columns_tablet'                      => $cols_small,
          'columns_mobile'                      => $cols_small,
          'rows_desktop'                        => 0,
          'rows_tablet'                         => 0,
          'rows_mobile'                         => 0,

          'show_title'                          => $show_title_desktop,
          'show_title_desktop'                  => $show_title_desktop,
          'show_title_tablet'                   => $show_title_tablet,
          'show_title_mobile'                   => $show_title_mobile,

          'show_price'                          => $show_price_desktop,
          'show_price_desktop'                  => $show_price_desktop,
          'show_price_tablet'                   => $show_price_tablet,
          'show_price_mobile'                   => $show_price_mobile,

          'show_rating'                         => $show_rating_desktop,
          'show_rating_desktop'                 => $show_rating_desktop,
          'show_rating_tablet'                  => $show_rating_tablet,
          'show_rating_mobile'                   => $show_rating_mobile,

          'show_add_to_cart'                    => $show_add_to_cart_desktop,
          'show_add_to_cart_desktop'            => $show_add_to_cart_desktop,
          'show_add_to_cart_tablet'             => $show_add_to_cart_tablet,
          'show_add_to_cart_mobile'             => $show_add_to_cart_mobile,

          'show_description'                    => $show_description_desktop,
          'show_description_desktop'            => $show_description_desktop,
          'show_description_tablet'             => $show_description_tablet,
          'show_description_mobile'             => $show_description_mobile,

          'show_view_more'                      => $show_view_more_desktop,
          'show_view_more_desktop'              => $show_view_more_desktop,
          'show_view_more_tablet'               => $show_view_more_tablet,
          'show_view_more_mobile'               => $show_view_more_mobile,

          'view_more_label'                     => (isset($out['view_more_label']) && $out['view_more_label'] !== '') ? sanitize_text_field($out['view_more_label']) : 'View more',
          'options_enable'                      => !empty($out['small_options_enable']) ? 1 : 0,
          'options_open'                        => !empty($out['small_options_open']) ? 1 : 0,
          'options_label'                       => (isset($out['options_label']) && $out['options_label'] !== '') ? sanitize_text_field($out['options_label']) : 'Options',

          'list_mobile_layout'                  => $list_mobile_layout_desktop,
          'list_mobile_layout_desktop'          => $list_mobile_layout_desktop,
          'list_mobile_layout_tablet'           => $list_mobile_layout_tablet,
          'list_mobile_layout_mobile'           => $list_mobile_layout_mobile,

          'list_mobile_image_width'             => $list_mobile_image_width_desktop,
          'list_mobile_image_width_desktop'     => $list_mobile_image_width_desktop,
          'list_mobile_image_width_tablet'      => $list_mobile_image_width_tablet,
          'list_mobile_image_width_mobile'      => $list_mobile_image_width_mobile,

          'list_mobile_vertical_align'          => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_desktop'  => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_tablet'   => $list_mobile_vertical_align_tablet,
          'list_mobile_vertical_align_mobile'   => $list_mobile_vertical_align_mobile,

          'title_align'                         => $title_align_desktop,
          'title_align_desktop'                 => $title_align_desktop,
          'title_align_tablet'                  => $title_align_tablet,
          'title_align_mobile'                  => $title_align_mobile,

          'desc_align'                          => $desc_align_desktop,
          'desc_align_desktop'                  => $desc_align_desktop,
          'desc_align_tablet'                   => $desc_align_tablet,
          'desc_align_mobile'                   => $desc_align_mobile,

          'price_align'                         => $price_align_desktop,
          'price_align_desktop'                 => $price_align_desktop,
          'price_align_tablet'                  => $price_align_tablet,
          'price_align_mobile'                  => $price_align_mobile,

          'more_align'                          => $more_align_desktop,
          'more_align_desktop'                  => $more_align_desktop,
          'more_align_tablet'                   => $more_align_tablet,
          'more_align_mobile'                   => $more_align_mobile,

          'card_justify_content'                => $card_justify_content_desktop,
          'card_justify_content_desktop'        => $card_justify_content_desktop,
          'card_justify_content_tablet'         => $card_justify_content_tablet,
          'card_justify_content_mobile'         => $card_justify_content_mobile,
        ],
        [
          'id'                                  => 'large_grid',
          'type'                                => 'grid',
          'label'                               => 'Grid',
          'icon'                                => [],
          'columns'                             => $cols_large,
          'rows'                                => 0,
          'columns_desktop'                     => $cols_large,
          'columns_tablet'                      => $cols_large,
          'columns_mobile'                      => $cols_large,
          'rows_desktop'                        => 0,
          'rows_tablet'                         => 0,
          'rows_mobile'                         => 0,

          'show_title'                          => $show_title_desktop,
          'show_title_desktop'                  => $show_title_desktop,
          'show_title_tablet'                   => $show_title_tablet,
          'show_title_mobile'                   => $show_title_mobile,

          'show_price'                          => $show_price_desktop,
          'show_price_desktop'                  => $show_price_desktop,
          'show_price_tablet'                   => $show_price_tablet,
          'show_price_mobile'                   => $show_price_mobile,

          'show_rating'                         => $show_rating_desktop,
          'show_rating_desktop'                 => $show_rating_desktop,
          'show_rating_tablet'                  => $show_rating_tablet,
          'show_rating_mobile'                   => $show_rating_mobile,

          'show_add_to_cart'                    => $show_add_to_cart_desktop,
          'show_add_to_cart_desktop'            => $show_add_to_cart_desktop,
          'show_add_to_cart_tablet'             => $show_add_to_cart_tablet,
          'show_add_to_cart_mobile'             => $show_add_to_cart_mobile,

          'show_description'                    => $show_description_desktop,
          'show_description_desktop'            => $show_description_desktop,
          'show_description_tablet'             => $show_description_tablet,
          'show_description_mobile'             => $show_description_mobile,

          'show_view_more'                      => $show_view_more_desktop,
          'show_view_more_desktop'              => $show_view_more_desktop,
          'show_view_more_tablet'               => $show_view_more_tablet,
          'show_view_more_mobile'               => $show_view_more_mobile,

          'view_more_label'                     => (isset($out['view_more_label']) && $out['view_more_label'] !== '') ? sanitize_text_field($out['view_more_label']) : 'View more',
          'options_enable'                      => 0,
          'options_open'                        => 0,
          'options_label'                       => 'Options',

          'list_mobile_layout'                  => $list_mobile_layout_desktop,
          'list_mobile_layout_desktop'          => $list_mobile_layout_desktop,
          'list_mobile_layout_tablet'           => $list_mobile_layout_tablet,
          'list_mobile_layout_mobile'           => $list_mobile_layout_mobile,

          'list_mobile_image_width'             => $list_mobile_image_width_desktop,
          'list_mobile_image_width_desktop'     => $list_mobile_image_width_desktop,
          'list_mobile_image_width_tablet'      => $list_mobile_image_width_tablet,
          'list_mobile_image_width_mobile'      => $list_mobile_image_width_mobile,

          'list_mobile_vertical_align'          => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_desktop'  => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_tablet'   => $list_mobile_vertical_align_tablet,
          'list_mobile_vertical_align_mobile'   => $list_mobile_vertical_align_mobile,

          'title_align'                         => $title_align_desktop,
          'title_align_desktop'                 => $title_align_desktop,
          'title_align_tablet'                  => $title_align_tablet,
          'title_align_mobile'                  => $title_align_mobile,

          'desc_align'                          => $desc_align_desktop,
          'desc_align_desktop'                  => $desc_align_desktop,
          'desc_align_tablet'                   => $desc_align_tablet,
          'desc_align_mobile'                   => $desc_align_mobile,

          'price_align'                         => $price_align_desktop,
          'price_align_desktop'                 => $price_align_desktop,
          'price_align_tablet'                  => $price_align_tablet,
          'price_align_mobile'                  => $price_align_mobile,

          'more_align'                          => $more_align_desktop,
          'more_align_desktop'                  => $more_align_desktop,
          'more_align_tablet'                   => $more_align_tablet,
          'more_align_mobile'                   => $more_align_mobile,

          'card_justify_content'                => $card_justify_content_desktop,
          'card_justify_content_desktop'        => $card_justify_content_desktop,
          'card_justify_content_tablet'         => $card_justify_content_tablet,
          'card_justify_content_mobile'         => $card_justify_content_mobile,
        ],
        [
          'id'                                  => 'list',
          'type'                                => 'list',
          'label'                               => 'List',
          'icon'                                => [],
          'columns'                             => 1,
          'rows'                                => 0,
          'columns_desktop'                     => 1,
          'columns_tablet'                      => 1,
          'columns_mobile'                      => 1,
          'rows_desktop'                        => 0,
          'rows_tablet'                         => 0,
          'rows_mobile'                         => 0,

          'show_title'                          => $show_title_desktop,
          'show_title_desktop'                  => $show_title_desktop,
          'show_title_tablet'                   => $show_title_tablet,
          'show_title_mobile'                   => $show_title_mobile,

          'show_price'                          => $show_price_desktop,
          'show_price_desktop'                  => $show_price_desktop,
          'show_price_tablet'                   => $show_price_tablet,
          'show_price_mobile'                   => $show_price_mobile,

          'show_rating'                         => $show_rating_desktop,
          'show_rating_desktop'                 => $show_rating_desktop,
          'show_rating_tablet'                  => $show_rating_tablet,
          'show_rating_mobile'                   => $show_rating_mobile,

          'show_add_to_cart'                    => $show_add_to_cart_desktop,
          'show_add_to_cart_desktop'            => $show_add_to_cart_desktop,
          'show_add_to_cart_tablet'             => $show_add_to_cart_tablet,
          'show_add_to_cart_mobile'             => $show_add_to_cart_mobile,

          'show_description'                    => $show_description_desktop,
          'show_description_desktop'            => $show_description_desktop,
          'show_description_tablet'             => $show_description_tablet,
          'show_description_mobile'             => $show_description_mobile,

          'show_view_more'                      => $show_view_more_desktop,
          'show_view_more_desktop'              => $show_view_more_desktop,
          'show_view_more_tablet'               => $show_view_more_tablet,
          'show_view_more_mobile'               => $show_view_more_mobile,

          'view_more_label'                     => (isset($out['view_more_label']) && $out['view_more_label'] !== '') ? sanitize_text_field($out['view_more_label']) : 'View more',
          'options_enable'                      => 0,
          'options_open'                        => 0,
          'options_label'                       => 'Options',

          'list_mobile_layout'                  => $list_mobile_layout_desktop,
          'list_mobile_layout_desktop'          => $list_mobile_layout_desktop,
          'list_mobile_layout_tablet'           => $list_mobile_layout_tablet,
          'list_mobile_layout_mobile'           => $list_mobile_layout_mobile,

          'list_mobile_image_width'             => $list_mobile_image_width_desktop,
          'list_mobile_image_width_desktop'     => $list_mobile_image_width_desktop,
          'list_mobile_image_width_tablet'      => $list_mobile_image_width_tablet,
          'list_mobile_image_width_mobile'      => $list_mobile_image_width_mobile,

          'list_mobile_vertical_align'          => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_desktop'  => $list_mobile_vertical_align_desktop,
          'list_mobile_vertical_align_tablet'   => $list_mobile_vertical_align_tablet,
          'list_mobile_vertical_align_mobile'   => $list_mobile_vertical_align_mobile,

          'title_align'                         => $title_align_desktop,
          'title_align_desktop'                 => $title_align_desktop,
          'title_align_tablet'                  => $title_align_tablet,
          'title_align_mobile'                  => $titleAlign_mobile ?? $title_align_mobile,

          'desc_align'                          => $desc_align_desktop,
          'desc_align_desktop'                  => $desc_align_desktop,
          'desc_align_tablet'                   => $desc_align_tablet,
          'desc_align_mobile'                   => $desc_align_mobile,

          'price_align'                         => $price_align_desktop,
          'price_align_desktop'                 => $price_align_desktop,
          'price_align_tablet'                  => $price_align_tablet,
          'price_align_mobile'                  => $price_align_mobile,

          'more_align'                          => $more_align_desktop,
          'more_align_desktop'                  => $more_align_desktop,
          'more_align_tablet'                   => $more_align_tablet,
          'more_align_mobile'                   => $more_align_mobile,

          'card_justify_content'                => $card_justify_content_desktop,
          'card_justify_content_desktop'        => $card_justify_content_desktop,
          'card_justify_content_tablet'         => $card_justify_content_tablet,
          'card_justify_content_mobile'         => $card_justify_content_mobile,
        ],
      ];

      $out['layout'] = $layout;
    }

    if (empty($layouts)) {
      $layouts[] = [
        'id'                                  => 'default_grid',
        'type'                                => 'grid',
        'label'                               => 'Grid',
        'icon'                                => [],
        'columns'                             => 4,
        'rows'                                => 0,
        'columns_desktop'                     => 4,
        'columns_tablet'                      => 4,
        'columns_mobile'                      => 4,
        'rows_desktop'                        => 0,
        'rows_tablet'                         => 0,
        'rows_mobile'                         => 0,

        'show_title'                          => 1,
        'show_title_desktop'                  => 1,
        'show_title_tablet'                   => 1,
        'show_title_mobile'                   => 1,

        'show_price'                          => 1,
        'show_price_desktop'                  => 1,
        'show_price_tablet'                   => 1,
        'show_price_mobile'                   => 1,

        'show_rating'                         => 1,
        'show_rating_desktop'                 => 1,
        'show_rating_tablet'                  => 1,
        'show_rating_mobile'                   => 1,

        'show_add_to_cart'                    => 1,
        'show_add_to_cart_desktop'            => 1,
        'show_add_to_cart_tablet'             => 1,
        'show_add_to_cart_mobile'             => 1,

        'show_description'                    => 0,
        'show_description_desktop'            => 0,
        'show_description_tablet'             => 0,
        'show_description_mobile'             => 0,

        'show_view_more'                      => 1,
        'show_view_more_desktop'              => 1,
        'show_view_more_tablet'               => 1,
        'show_view_more_mobile'               => 1,

        'view_more_label'                     => 'View more',
        'options_enable'                      => 1,
        'options_open'                        => 0,
        'options_label'                       => 'Options',

        'list_mobile_layout'                  => 'row',
        'list_mobile_layout_desktop'          => 'row',
        'list_mobile_layout_tablet'           => 'row',
        'list_mobile_layout_mobile'           => 'column',

        'list_mobile_image_width'             => 28,
        'list_mobile_image_width_desktop'     => 28,
        'list_mobile_image_width_tablet'      => 34,
        'list_mobile_image_width_mobile'      => 40,

        'list_mobile_vertical_align'          => 'flex-start',
        'list_mobile_vertical_align_desktop'  => 'flex-start',
        'list_mobile_vertical_align_tablet'   => 'flex-start',
        'list_mobile_vertical_align_mobile'   => 'flex-start',

        'title_align'                         => 'left',
        'title_align_desktop'                 => 'left',
        'title_align_tablet'                  => 'left',
        'title_align_mobile'                  => 'left',

        'desc_align'                          => 'left',
        'desc_align_desktop'                  => 'left',
        'desc_align_tablet'                   => 'left',
        'desc_align_mobile'                   => 'left',

        'price_align'                         => 'left',
        'price_align_desktop'                 => 'left',
        'price_align_tablet'                  => 'left',
        'price_align_mobile'                  => 'left',

        'more_align'                          => 'left',
        'more_align_desktop'                  => 'left',
        'more_align_tablet'                   => 'left',
        'more_align_mobile'                   => 'left',

        'card_justify_content'                => '',
        'card_justify_content_desktop'        => '',
        'card_justify_content_tablet'         => '',
        'card_justify_content_mobile'         => '',
      ];
    }

    $active_id = isset($out['layout']) ? preg_replace('/[^a-zA-Z0-9\_\-]/', '', (string)$out['layout']) : '';
    $active = null;

    foreach ($layouts as $m) {
      if ($m['id'] === $active_id) { $active = $m; break; }
    }

    if (!$active) {
      $active = $layouts[0];
      $active_id = $active['id'];
    }

    $device = isset($out['responsive_device']) ? sanitize_key((string)$out['responsive_device']) : 'desktop';
    if (!in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
      $device = 'desktop';
    }

    $cols_key = 'columns_' . $device;
    $rows_key = 'rows_' . $device;

    $resolved_cols = isset($active[$cols_key]) ? (int)$active[$cols_key] : (int)$active['columns'];
    $resolved_rows = isset($active[$rows_key]) ? (int)$active[$rows_key] : (int)$active['rows'];

    if (($active['type'] ?? 'grid') === 'list') {
      $resolved_cols = 1;
    }

    $resolved_cols = max(1, min(6, $resolved_cols));
    $resolved_rows = max(0, $resolved_rows);

    $resolved_show_title       = self::pick_layout_bool($active, 'show_title', $device, 1);
    $resolved_show_price       = self::pick_layout_bool($active, 'show_price', $device, 1);
    $resolved_show_rating      = self::pick_layout_bool($active, 'show_rating', $device, 1);
    $resolved_show_add_to_cart = self::pick_layout_bool($active, 'show_add_to_cart', $device, 1);
    $resolved_show_description = self::pick_layout_bool($active, 'show_description', $device, 0);
    $resolved_show_view_more   = self::pick_layout_bool($active, 'show_view_more', $device, 1);

    $resolved_list_mobile_layout = self::pick_layout_choice($active, 'list_mobile_layout', $device, 'row', ['row', 'column']);
    $resolved_list_mobile_image_width = self::pick_layout_number($active, 'list_mobile_image_width', $device, 28, 15, 80);
    $resolved_list_mobile_vertical_align = self::pick_layout_choice($active, 'list_mobile_vertical_align', $device, 'flex-start', ['flex-start', 'center', 'flex-end']);

    $resolved_title_align = self::pick_layout_choice($active, 'title_align', $device, 'left', ['left', 'center', 'right']);
    $resolved_desc_align  = self::pick_layout_choice($active, 'desc_align', $device, 'left', ['left', 'center', 'right']);
    $resolved_price_align = self::pick_layout_choice($active, 'price_align', $device, 'left', ['left', 'center', 'right']);
    $resolved_more_align  = self::pick_layout_choice($active, 'more_align', $device, 'left', ['left', 'center', 'right']);
    $resolved_card_justify_content = self::pick_layout_choice($active, 'card_justify_content', $device, '', ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly']);

    $out['layouts'] = $layouts;
    $out['layout']  = $active_id;

    $out['layout_id']   = $active_id;
    $out['layout_type'] = $active['type'];
    $out['responsive_device'] = $device;

    $out['columns']          = $resolved_cols;
    $out['rows']             = $resolved_rows;
    $out['columns_desktop']  = (int)$active['columns_desktop'];
    $out['columns_tablet']   = (int)$active['columns_tablet'];
    $out['columns_mobile']   = (int)$active['columns_mobile'];
    $out['rows_desktop']     = (int)$active['rows_desktop'];
    $out['rows_tablet']      = (int)$active['rows_tablet'];
    $out['rows_mobile']      = (int)$active['rows_mobile'];

    $out['show_title']             = $resolved_show_title;
    $out['show_title_desktop']     = self::sanitize_bool_value($active['show_title_desktop'] ?? ($active['show_title'] ?? 1), 1);
    $out['show_title_tablet']      = self::sanitize_bool_value($active['show_title_tablet'] ?? $out['show_title_desktop'], $out['show_title_desktop']);
    $out['show_title_mobile']      = self::sanitize_bool_value($active['show_title_mobile'] ?? $out['show_title_tablet'], $out['show_title_tablet']);

    $out['show_price']             = $resolved_show_price;
    $out['show_price_desktop']     = self::sanitize_bool_value($active['show_price_desktop'] ?? ($active['show_price'] ?? 1), 1);
    $out['show_price_tablet']      = self::sanitize_bool_value($active['show_price_tablet'] ?? $out['show_price_desktop'], $out['show_price_desktop']);
    $out['show_price_mobile']      = self::sanitize_bool_value($active['show_price_mobile'] ?? $out['show_price_tablet'], $out['show_price_tablet']);

    $out['show_rating']            = $resolved_show_rating;
    $out['show_rating_desktop']    = self::sanitize_bool_value($active['show_rating_desktop'] ?? ($active['show_rating'] ?? 1), 1);
    $out['show_rating_tablet']     = self::sanitize_bool_value($active['show_rating_tablet'] ?? $out['show_rating_desktop'], $out['show_rating_desktop']);
    $out['show_rating_mobile']     = self::sanitize_bool_value($active['show_rating_mobile'] ?? $out['show_rating_tablet'], $out['show_rating_tablet']);

    $out['show_add_to_cart']       = $resolved_show_add_to_cart;
    $out['show_add_to_cart_desktop'] = self::sanitize_bool_value($active['show_add_to_cart_desktop'] ?? ($active['show_add_to_cart'] ?? 1), 1);
    $out['show_add_to_cart_tablet']  = self::sanitize_bool_value($active['show_add_to_cart_tablet'] ?? $out['show_add_to_cart_desktop'], $out['show_add_to_cart_desktop']);
    $out['show_add_to_cart_mobile']  = self::sanitize_bool_value($active['show_add_to_cart_mobile'] ?? $out['show_add_to_cart_tablet'], $out['show_add_to_cart_tablet']);

    $out['show_description']       = $resolved_show_description;
    $out['show_description_desktop'] = self::sanitize_bool_value($active['show_description_desktop'] ?? ($active['show_description'] ?? 0), 0);
    $out['show_description_tablet']  = self::sanitize_bool_value($active['show_description_tablet'] ?? $out['show_description_desktop'], $out['show_description_desktop']);
    $out['show_description_mobile']  = self::sanitize_bool_value($active['show_description_mobile'] ?? $out['show_description_tablet'], $out['show_description_tablet']);

    $out['show_view_more']         = $resolved_show_view_more;
    $out['show_view_more_desktop'] = self::sanitize_bool_value($active['show_view_more_desktop'] ?? ($active['show_view_more'] ?? 1), 1);
    $out['show_view_more_tablet']  = self::sanitize_bool_value($active['show_view_more_tablet'] ?? $out['show_view_more_desktop'], $out['show_view_more_desktop']);
    $out['show_view_more_mobile']  = self::sanitize_bool_value($active['show_view_more_mobile'] ?? $out['show_view_more_tablet'], $out['show_view_more_tablet']);

    $out['view_more_label']  = $active['view_more_label'];

    $out['small_options_enable'] = (int)$active['options_enable'];
    $out['small_options_open']   = (int)$active['options_open'];
    $out['options_label']        = $active['options_label'];

    $out['list_mobile_layout']                = $resolved_list_mobile_layout;
    $out['list_mobile_layout_desktop']        = $active['list_mobile_layout_desktop'] ?? 'row';
    $out['list_mobile_layout_tablet']         = $active['list_mobile_layout_tablet'] ?? ($active['list_mobile_layout_desktop'] ?? 'row');
    $out['list_mobile_layout_mobile']         = $active['list_mobile_layout_mobile'] ?? ($active['list_mobile_layout_tablet'] ?? 'row');

    $out['list_mobile_image_width']           = $resolved_list_mobile_image_width;
    $out['list_mobile_image_width_desktop']   = isset($active['list_mobile_image_width_desktop']) ? (float)$active['list_mobile_image_width_desktop'] : 28;
    $out['list_mobile_image_width_tablet']    = isset($active['list_mobile_image_width_tablet']) ? (float)$active['list_mobile_image_width_tablet'] : $out['list_mobile_image_width_desktop'];
    $out['list_mobile_image_width_mobile']    = isset($active['list_mobile_image_width_mobile']) ? (float)$active['list_mobile_image_width_mobile'] : $out['list_mobile_image_width_tablet'];

    $out['list_mobile_vertical_align']        = $resolved_list_mobile_vertical_align;
    $out['list_mobile_vertical_align_desktop']= $active['list_mobile_vertical_align_desktop'] ?? 'flex-start';
    $out['list_mobile_vertical_align_tablet'] = $active['list_mobile_vertical_align_tablet'] ?? ($active['list_mobile_vertical_align_desktop'] ?? 'flex-start');
    $out['list_mobile_vertical_align_mobile'] = $active['list_mobile_vertical_align_mobile'] ?? ($active['list_mobile_vertical_align_tablet'] ?? 'flex-start');

    $out['title_align']                       = $resolved_title_align;
    $out['title_align_desktop']               = $active['title_align_desktop'] ?? 'left';
    $out['title_align_tablet']                = $active['title_align_tablet'] ?? ($active['title_align_desktop'] ?? 'left');
    $out['title_align_mobile']                = $active['title_align_mobile'] ?? ($active['title_align_tablet'] ?? 'left');

    $out['desc_align']                        = $resolved_desc_align;
    $out['desc_align_desktop']                = $active['desc_align_desktop'] ?? 'left';
    $out['desc_align_tablet']                 = $active['desc_align_tablet'] ?? ($active['desc_align_desktop'] ?? 'left');
    $out['desc_align_mobile']                 = $active['desc_align_mobile'] ?? ($active['desc_align_tablet'] ?? 'left');

    $out['price_align']                       = $resolved_price_align;
    $out['price_align_desktop']               = $active['price_align_desktop'] ?? 'left';
    $out['price_align_tablet']                = $active['price_align_tablet'] ?? ($active['price_align_desktop'] ?? 'left');
    $out['price_align_mobile']                = $active['price_align_mobile'] ?? ($active['price_align_tablet'] ?? 'left');

    $out['more_align']                        = $resolved_more_align;
    $out['more_align_desktop']                = $active['more_align_desktop'] ?? 'left';
    $out['more_align_tablet']                 = $active['more_align_tablet'] ?? ($active['more_align_desktop'] ?? 'left');
    $out['more_align_mobile']                 = $active['more_align_mobile'] ?? ($active['more_align_tablet'] ?? 'left');

    $out['card_justify_content']              = $resolved_card_justify_content;
    $out['card_justify_content_desktop']      = $active['card_justify_content_desktop'] ?? '';
    $out['card_justify_content_tablet']       = $active['card_justify_content_tablet'] ?? ($active['card_justify_content_desktop'] ?? '');
    $out['card_justify_content_mobile']       = $active['card_justify_content_mobile'] ?? ($active['card_justify_content_tablet'] ?? '');

    $out['layout_switcher_enable'] = (count($layouts) > 1) ? 1 : 0;

    $ppo = 0;
    if ($resolved_rows > 0) {
      $ppo = ($out['layout_type'] === 'list') ? $resolved_rows : ($resolved_rows * max(1, (int)$resolved_cols));
    }
    $out['per_page_override'] = $ppo;

    $out['sort_enabled'] = !empty($out['sort_enabled']) ? 1 : 0;

    if (isset($out['sort_default']) && $out['sort_default'] !== '') {
      $out['sort_default'] = sanitize_key($out['sort_default']);
    } else {
      if (!isset($out['sort_default'])) $out['sort_default'] = '';
    }

    if (isset($out['sort_label_text']) && $out['sort_label_text'] !== '') {
      $out['sort_label_text'] = sanitize_text_field($out['sort_label_text']);
    } else {
      if (!isset($out['sort_label_text'])) $out['sort_label_text'] = 'Sort';
    }

    $out['sort_label_show'] = !empty($out['sort_label_show']) ? 1 : 0;

    $layout_mode = isset($out['sort_summary_layout']) ? sanitize_key((string)$out['sort_summary_layout']) : 'summary_left_sort_right';
    if (!in_array($layout_mode, ['summary_left_sort_right', 'sort_left_summary_right'], true)) {
      $layout_mode = 'summary_left_sort_right';
    }
    $out['sort_summary_layout'] = $layout_mode;

    $out['show_summary'] = array_key_exists('show_summary', $out)
      ? (!empty($out['show_summary']) ? 1 : 0)
      : 1;

    $shape = isset($out['loading_shape']) ? sanitize_key((string)$out['loading_shape']) : 'circle';
    if (!in_array($shape, ['circle', 'square', 'rounded', 'custom'], true)) $shape = 'circle';
    $out['loading_shape'] = $shape;

    $out['loading_icon'] = (isset($out['loading_icon']) && is_array($out['loading_icon']))
      ? $out['loading_icon']
      : [];

    $out['loading_icon_spin'] = !empty($out['loading_icon_spin']) ? 1 : 0;

    $ph_mode = isset($out['image_placeholder_mode']) ? sanitize_key((string)$out['image_placeholder_mode']) : 'none';
    if (!in_array($ph_mode, ['none', 'text', 'image'], true)) $ph_mode = 'none';
    $out['image_placeholder_mode'] = $ph_mode;

    $out['image_placeholder_text'] = isset($out['image_placeholder_text']) && $out['image_placeholder_text'] !== ''
      ? sanitize_text_field((string)$out['image_placeholder_text'])
      : 'Loading image';

    if (isset($out['image_placeholder_image']) && is_array($out['image_placeholder_image'])) {
      $out['image_placeholder_image'] = [
        'id'  => !empty($out['image_placeholder_image']['id']) ? absint($out['image_placeholder_image']['id']) : 0,
        'url' => !empty($out['image_placeholder_image']['url']) ? esc_url_raw($out['image_placeholder_image']['url']) : '',
      ];
    } else {
      $out['image_placeholder_image'] = ['id' => 0, 'url' => ''];
    }

    return $out;
  }

  private static function get_default_orderby_from_set(array $set): string {
    foreach (($set['filters'] ?? []) as $f) {
      if (($f['type'] ?? '') === 'orderby' && !empty($f['enabled'])) {
        $d = sanitize_key($f['default'] ?? '');
        return $d ?: 'menu_order';
      }
    }

    if (!empty($set['sort']) && is_array($set['sort']) && !empty($set['sort']['enabled'])) {
      $d = sanitize_key($set['sort']['default'] ?? '');
      return $d ?: 'menu_order';
    }

    return '';
  }
}

trait GWSFB_Render_Results {

  private static function get_enabled_filter_type_map(array $set): array {
    $map = [
      'attribute' => false,
      'price'     => false,
      'stock'     => false,
      'category'  => false,
    ];

    foreach (($set['filters'] ?? []) as $f) {
      if (!is_array($f) || empty($f['enabled'])) continue;
      $type = sanitize_key((string)($f['type'] ?? ''));
      if ($type !== '' && array_key_exists($type, $map)) {
        $map[$type] = true;
      }
    }

    return $map;
  }

  private static function sanitize_stock_status_list($raw): array {
    $allowed = ['instock' => true, 'outofstock' => true, 'onbackorder' => true];
    $out = [];

    if (!is_array($raw)) return [];

    foreach ($raw as $s) {
      $k = sanitize_key((string)$s);
      if ($k && isset($allowed[$k])) {
        $out[$k] = true;
      }
    }

    return array_values(array_keys($out));
  }

  private static function get_default_stock_statuses_from_set(array $set): array {
    foreach (($set['filters'] ?? []) as $f) {
      if (!is_array($f) || empty($f['enabled'])) continue;
      if (sanitize_key((string)($f['type'] ?? '')) !== 'stock') continue;

      $statuses = self::sanitize_stock_status_list($f['statuses'] ?? []);
      if (empty($statuses)) {
        $statuses = ['instock'];
      }

      return $statuses;
    }

    return [];
  }

  private static function apply_initial_filter_defaults(array $set, array $req): array {
    $out = $req;
    $enabled = self::get_enabled_filter_type_map($set);

    if (!empty($enabled['stock'])) {
      $has_stock_statuses = array_key_exists('stock_statuses', $out);
      $has_stock_flag = !empty($out['stock']) && sanitize_key((string)$out['stock']) === 'instock';

      if (!$has_stock_statuses && !$has_stock_flag) {
        $defaults = self::get_default_stock_statuses_from_set($set);

        if (!empty($defaults)) {
          if (count($defaults) === 1 && $defaults[0] === 'instock') {
            $out['stock'] = 'instock';
          } else {
            $out['stock_statuses'] = $defaults;
          }
        }
      }
    }

    return $out;
  }

  public static function render_results(array $set, array $req, array $view): void {
    $view = self::sanitize_view($view);
    $req  = self::apply_initial_filter_defaults($set, $req);

    if (empty($req['orderby'])) {
      $req['orderby'] = method_exists(__CLASS__, 'get_default_orderby_from_set')
        ? self::get_default_orderby_from_set($set)
        : '';
      if (empty($req['orderby'])) $req['orderby'] = 'menu_order';
    }

    $group = isset($req['group']) ? sanitize_key((string)$req['group']) : '';
    if ($group === '' && !empty($set['id'])) {
      $group = 'g' . sanitize_text_field((string)$set['id']);
    }
    $view['group'] = $group;

    $page = isset($req['page']) ? max(1, (int)$req['page']) : 1;

    $base_req = $req;
    unset($base_req['page'], $base_req['paged']);

    $ppo = isset($view['per_page_override']) ? (int)$view['per_page_override'] : 0;
    if ($ppo > 0) {
      $base_req['per_page'] = $ppo;
    }

    $base_args = GWSFB_Query::build_args($set, $base_req);

    $per_page = isset($base_args['posts_per_page']) && (int)$base_args['posts_per_page'] > 0
      ? (int)$base_args['posts_per_page']
      : (int)get_option('posts_per_page', 12);

    if ($per_page < 1) $per_page = 12;

    $count_args = $base_args;
    $count_args['posts_per_page'] = -1;
    unset($count_args['offset'], $count_args['paged']);

    $count_query = new WP_Query($count_args);
    $total = (int)$count_query->found_posts;

    wp_reset_postdata();

    $max_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
    if ($max_pages < 1) $max_pages = 1;

    if ($page > $max_pages) $page = $max_pages;

    $show_summary = array_key_exists('show_summary', $view) ? !empty($view['show_summary']) : true;
    $sort_enabled = !empty($view['sort_enabled']);
    $orderby      = isset($req['orderby']) ? sanitize_key($req['orderby']) : '';

    $layout_mode = isset($view['sort_summary_layout'])
      ? sanitize_key((string)$view['sort_summary_layout'])
      : 'summary_left_sort_right';
    if (!in_array($layout_mode, ['summary_left_sort_right', 'sort_left_summary_right'], true)) {
      $layout_mode = 'summary_left_sort_right';
    }

    $summary_html = '';
    if ($show_summary) {
      $summary_html  = '<div class="gwsfb-page-summary">';
      $summary_html .= '<span class="gwsfb-page-summary__label">' . esc_html__('Total:', 'gwsfb') . '</span>';
      $summary_html .= '<span class="gwsfb-page-summary__total" data-total="' . (int)$total . '">' . (int)$total . '</span>';
      $summary_html .= '<span class="gwsfb-page-summary__sep"> / </span>';
      $summary_html .= '<span class="gwsfb-page-summary__page" data-page="' . (int)$page . '" data-pages="' . (int)$max_pages . '">';
      $summary_html .= sprintf('Page %d of %d', $page, $max_pages);
      $summary_html .= '</span>';
      $summary_html .= '</div>';
    }

    $layout_html = '';
    if (!empty($view['layout_switcher_enable']) && method_exists(__CLASS__, 'render_layout_switcher_only')) {
      ob_start();
      self::render_layout_switcher_only($view);
      $layout_html = (string)ob_get_clean();
    }

    if ($layout_html !== '' || $summary_html !== '' || $sort_enabled) {
      echo '<div class="gwsfb-headerrow gwsfb-headerrow--' . esc_attr($layout_mode) . '" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';

      if ($layout_mode === 'sort_left_summary_right') {
        echo '<div class="gwsfb-headerrow__left" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
          if ($sort_enabled && method_exists(__CLASS__, 'render_sort_bar')) {
            self::render_sort_bar($set, $orderby, $view);
          }
        echo '</div>';

        echo '<div class="gwsfb-headerrow__right" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
          echo $layout_html;
          echo $summary_html;
        echo '</div>';
      } else {
        echo '<div class="gwsfb-headerrow__left" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
          echo $layout_html;
          echo $summary_html;
        echo '</div>';

        echo '<div class="gwsfb-headerrow__right" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
          if ($sort_enabled && method_exists(__CLASS__, 'render_sort_bar')) {
            self::render_sort_bar($set, $orderby, $view);
          }
        echo '</div>';
      }

      echo '</div>';
    }

    if ($total === 0) {
      echo '<div class="gwsfb-results__wrap gwsfb-results__wrap--empty">';
      echo '<div class="gwsfb-results__grid" style="display:block;"><div class="gwsfb__no">No products found.</div></div>';
      echo '</div>';
      return;
    }

    $paged_args = $base_args;
    $paged_args['posts_per_page'] = $per_page;
    $paged_args['offset'] = $per_page * ($page - 1);
    unset($paged_args['paged']);

    if (method_exists(__CLASS__, 'is_attr_orderby') && self::is_attr_orderby($orderby)) {
      [$tax, $dir] = self::parse_attr_orderby($orderby);
      $q = self::query_with_attr_sort($paged_args, $tax, $dir);
    } else {
      $q = new WP_Query($paged_args);
    }

    $layout_id = isset($view['layout_id']) ? (string)$view['layout_id'] : (string)($view['layout'] ?? '');
    $layout_id = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $layout_id);
    if ($layout_id === '') $layout_id = 'default_grid';

    $layout_type = $view['layout_type'] ?? 'grid';
    if (!in_array($layout_type, ['grid', 'list'], true)) $layout_type = 'grid';

    $cols = ($layout_type === 'list') ? 1 : max(1, min(6, (int)($view['columns'] ?? 4)));

    $wrap_classes = [
      'gwsfb-results__wrap',
      'gwsfb-results__wrap--' . ($layout_type === 'list' ? 'list' : 'grid'),
      'elementor-repeater-item-' . $layout_id,
      'gwsfb-layout-item-' . $layout_id,
    ];

    $grid_classes = [
      'gwsfb-results__grid',
      'gwsfb-results__grid--' . ($layout_type === 'list' ? 'list' : 'grid'),
    ];

    echo '<div class="' . esc_attr(implode(' ', $wrap_classes)) . '" data-layout-id="' . esc_attr($layout_id) . '" data-layout-type="' . esc_attr($layout_type) . '">';

      $grid_style =
        '--gwsfb_cols:' . (int)$cols . ';' .
        'display:grid;' .
        'grid-template-columns:repeat(' . (int)$cols . ',minmax(0,1fr));' .
        'align-items:stretch;';

      if ($layout_type === 'list') {
        $grid_style =
          '--gwsfb_cols:1;' .
          'display:grid;' .
          'grid-template-columns:1fr;' .
          'align-items:stretch;';
      }

      echo '<div class="' . esc_attr(implode(' ', $grid_classes)) . '" style="' . esc_attr($grid_style) . '">';

      if ($q->have_posts()) {
        while ($q->have_posts()) {
          $q->the_post();
          $product = wc_get_product(get_the_ID());
          if (!$product) continue;
          echo self::render_product_card($product, $view);
        }
        wp_reset_postdata();
      } else {
        echo '<div class="gwsfb__no">No products found.</div>';
      }

      echo '</div>';
    echo '</div>';

    $pagination_mode = $set['results']['pagination'] ?? 'ajax';
    if ($pagination_mode === 'ajax' && $max_pages > 1) {
      $current_page = $page;

      echo '<div class="gwsfb__pager" data-max="' . (int)$max_pages . '">';
      for ($p = 1; $p <= $max_pages; $p++) {
        $classes = 'gwsfb__page button' . ($p === $current_page ? ' is-current' : '');
        echo '<button type="button" class="' . esc_attr($classes) . '" data-page="' . (int)$p . '"'
           . ($p === $current_page ? ' aria-current="page"' : '')
           . '>' . (int)$p . '</button>';
      }
      echo '</div>';
    }
  }

  private static function inject_product_image_class(string $html): string {
    $html = trim($html);
    if ($html === '') return '';

    if (stripos($html, '<img') === false) {
      return $html;
    }

    $html = preg_replace_callback('/<img\b([^>]*)>/i', function ($m) {
      $attrs = $m[1] ?? '';

      if (preg_match('/\sclass\s*=\s*"([^"]*)"/i', $attrs, $cm)) {
        $cls = trim($cm[1]);
        if (stripos($cls, 'gwsr-product-img') === false) {
          $cls .= ' gwsr-product-img';
        }
        $attrs = preg_replace('/\sclass\s*=\s*"[^"]*"/i', ' class="' . esc_attr(trim($cls)) . '"', $attrs, 1);
      } else {
        $attrs .= ' class="gwsr-product-img"';
      }

      return '<img' . $attrs . '>';
    }, $html, 1);

    return $html;
  }

  private static function render_image_placeholder(array $view): string {
    $mode = isset($view['image_placeholder_mode']) ? sanitize_key((string)$view['image_placeholder_mode']) : 'none';

    if ($mode === 'image') {
      $url = '';
      if (!empty($view['image_placeholder_image']) && is_array($view['image_placeholder_image'])) {
        $url = !empty($view['image_placeholder_image']['url']) ? esc_url($view['image_placeholder_image']['url']) : '';
      }

      if ($url !== '') {
        return '<span class="gwsr-img-ph gwsr-img-ph--image" aria-hidden="true"><img class="gwsr-img-ph-image" src="' . $url . '" alt="" /></span>';
      }
    }

    if ($mode === 'text') {
      $text = isset($view['image_placeholder_text']) && $view['image_placeholder_text'] !== ''
        ? sanitize_text_field((string)$view['image_placeholder_text'])
        : 'Loading image';

      return '<span class="gwsr-img-ph gwsr-img-ph--text" aria-hidden="true"><span class="gwsr-img-ph-text">' . esc_html($text) . '</span></span>';
    }

    return '';
  }

  private static function render_product_image_block(\WC_Product $product, string $link, array $view, string $anchor_style = ''): string {
    $img_html = self::inject_product_image_class((string)$product->get_image('woocommerce_thumbnail'));
    $placeholder_html = self::render_image_placeholder($view);

    $anchor_classes = ['gwsr-img'];
    if ($img_html !== '') {
      $anchor_classes[] = 'gwsr-img--has-image';
    } else {
      $anchor_classes[] = 'gwsr-img--no-image';
    }

    $html  = '<a class="' . esc_attr(implode(' ', $anchor_classes)) . '" href="' . esc_url($link) . '"';
    if ($anchor_style !== '') {
      $html .= ' style="' . esc_attr($anchor_style) . '"';
    }
    $html .= '>';
    $html .= $placeholder_html;
    $html .= $img_html;
    $html .= '</a>';

    return $html;
  }

  private static function render_product_card(\WC_Product $product, array $view): string {
    $pid   = $product->get_id();
    $link  = get_permalink($pid);
    $title = $product->get_name();

    $price_html  = $product->get_price_html();
    $rating_html = function_exists('wc_get_rating_html')
      ? wc_get_rating_html($product->get_average_rating())
      : '';
    $desc        = wp_trim_words(wp_strip_all_tags($product->get_short_description()), 22, '…');

    $layout_id = isset($view['layout_id']) ? (string)$view['layout_id'] : (string)($view['layout'] ?? '');
    $layout_id = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $layout_id);
    if ($layout_id === '') {
      $layout_id = 'default_grid';
    }

    $layout_type = $view['layout_type'] ?? 'grid';
    if (!in_array($layout_type, ['grid', 'list'], true)) {
      $layout_type = 'grid';
    }

    $layout_class = ($layout_type === 'list') ? 'list' : 'small_grid';

    $classes = [
      'gwsr-card',
      'gwsr-' . $layout_class,
      'gwsr-card--' . $layout_class,
      'gwsr-layout-' . $layout_id,
    ];

    $list_mobile_layout = isset($view['list_mobile_layout']) ? sanitize_key((string)$view['list_mobile_layout']) : 'row';
    if (!in_array($list_mobile_layout, ['row', 'column'], true)) {
      $list_mobile_layout = 'row';
    }

    $list_mobile_vertical_align = isset($view['list_mobile_vertical_align']) ? sanitize_key((string)$view['list_mobile_vertical_align']) : 'flex-start';
    if (!in_array($list_mobile_vertical_align, ['flex-start', 'center', 'flex-end'], true)) {
      $list_mobile_vertical_align = 'flex-start';
    }

    $list_mobile_image_width = isset($view['list_mobile_image_width']) ? (float)$view['list_mobile_image_width'] : 28;
    $list_mobile_image_width = max(15, min(80, $list_mobile_image_width));

    $title_align = isset($view['title_align']) ? sanitize_key((string)$view['title_align']) : 'left';
    if (!in_array($title_align, ['left', 'center', 'right'], true)) {
      $title_align = 'left';
    }

    $desc_align = isset($view['desc_align']) ? sanitize_key((string)$view['desc_align']) : 'left';
    if (!in_array($desc_align, ['left', 'center', 'right'], true)) {
      $desc_align = 'left';
    }

    $price_align = isset($view['price_align']) ? sanitize_key((string)$view['price_align']) : 'left';
    if (!in_array($price_align, ['left', 'center', 'right'], true)) {
      $price_align = 'left';
    }

    $more_align = isset($view['more_align']) ? sanitize_key((string)$view['more_align']) : 'left';
    if (!in_array($more_align, ['left', 'center', 'right'], true)) {
      $more_align = 'left';
    }

    $card_justify_content = isset($view['card_justify_content']) ? sanitize_key((string)$view['card_justify_content']) : '';
    if (!in_array($card_justify_content, ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], true)) {
      $card_justify_content = '';
    }

    $article_style_parts = [
      '--gwsr-list-mobile-image-width:' . $list_mobile_image_width . '%',
      '--gwsr-list-mobile-align:' . $list_mobile_vertical_align,
      '--gwsr-title-align:' . $title_align,
      '--gwsr-desc-align:' . $desc_align,
      '--gwsr-price-align:' . $price_align,
      '--gwsr-more-align:' . $more_align,
    ];

    $img_col_style_parts = [];
    $img_anchor_style_parts = [];
    $body_style_parts = [];

    if ($layout_type === 'list') {
      $classes[] = 'gwsr-list-mobile-' . $list_mobile_layout;
      $article_style_parts[] = 'display:flex';
      $article_style_parts[] = 'gap:16px';

      if ($list_mobile_layout === 'column') {
        $article_style_parts[] = 'flex-direction:column';
        $article_style_parts[] = 'align-items:stretch';

        $img_col_style_parts[] = 'flex:0 0 auto';
        $img_col_style_parts[] = 'width:100%';
        $img_col_style_parts[] = 'max-width:100%';
        $img_col_style_parts[] = 'display:flex';
        $img_col_style_parts[] = 'justify-content:center';
        $img_col_style_parts[] = 'align-self:center';

        $img_anchor_style_parts[] = 'max-width:100%';
        $img_anchor_style_parts[] = 'margin-left:auto';
        $img_anchor_style_parts[] = 'margin-right:auto';

        $body_style_parts[] = 'width:100%';
        $body_style_parts[] = 'min-width:0';
        $body_style_parts[] = 'display:flex';
        $body_style_parts[] = 'flex-direction:column';
      } else {
        $article_style_parts[] = 'flex-direction:row';
        $article_style_parts[] = 'align-items:' . $list_mobile_vertical_align;

        $img_col_style_parts[] = 'flex:0 0 ' . $list_mobile_image_width . '%';
        $img_col_style_parts[] = 'width:' . $list_mobile_image_width . '%';
        $img_col_style_parts[] = 'max-width:' . $list_mobile_image_width . '%';

        $img_anchor_style_parts[] = 'max-width:100%';

        $body_style_parts[] = 'flex:1 1 0';
        $body_style_parts[] = 'min-width:0';
        $body_style_parts[] = 'display:flex';
        $body_style_parts[] = 'flex-direction:column';
      }
    } else {
      if ($card_justify_content !== '') {
        $article_style_parts[] = 'justify-content:' . $card_justify_content;
      }
    }

    $article_style = implode(';', $article_style_parts);
    $img_col_style = implode(';', $img_col_style_parts);
    $img_anchor_style = implode(';', $img_anchor_style_parts);
    $body_style = implode(';', $body_style_parts);

    $view_more_label = isset($view['view_more_label']) && $view['view_more_label'] !== ''
      ? $view['view_more_label']
      : 'View more';

    $tag = isset($view['title_tag']) ? strtolower((string)$view['title_tag']) : 'h2';
    $allowed_tags = ['h1','h2','h3','h4','h5','h6','p','span','div'];
    if (!in_array($tag, $allowed_tags, true)) {
      $tag = 'h2';
    }

    $html  = '<article class="' . esc_attr(implode(' ', $classes)) . '"';
    if ($article_style !== '') {
      $html .= ' style="' . esc_attr($article_style) . '"';
    }
    $html .= '>';

    $html .= '<div class="gwsr-img-col"';
    if ($img_col_style !== '') {
      $html .= ' style="' . esc_attr($img_col_style) . '"';
    }
    $html .= '>';
    $html .= self::render_product_image_block($product, $link, $view, $img_anchor_style);
    $html .= '</div>';

    $html .= '<div class="gwsr-body"';
    if ($body_style !== '') {
      $html .= ' style="' . esc_attr($body_style) . '"';
    }
    $html .= '>';

      if (!empty($view['show_title'])) {
        $html .= '<' . $tag . ' class="gwsr-title">';
        $html .= '<a href="' . esc_url($link) . '">' . esc_html($title) . '</a>';
        $html .= '</' . $tag . '>';
      }

      if (!empty($view['show_rating']) || !empty($view['show_price'])) {
        $html .= '<div class="gwsr-row">';
          if (!empty($view['show_rating'])) {
            $html .= '<div class="gwsr-rating">' . ($rating_html ?: '') . '</div>';
          }
          if (!empty($view['show_price'])) {
            $html .= '<div class="gwsr-price" style="text-align:' . esc_attr($price_align) . ';">' . $price_html . '</div>';
          }
        $html .= '</div>';
      }

      if (!empty($view['show_description']) && $desc) {
        $html .= '<div class="gwsr-desc">' . esc_html($desc) . '</div>';
      }

      if (!empty($view['show_view_more'])) {
        $html .= '<a class="gwsr-morelink" href="' . esc_url($link) . '">' . esc_html($view_more_label) . '</a>';
      }

      $can_show_atc     = !empty($view['show_add_to_cart']);
      $can_show_options = ($layout_type === 'grid' && !empty($view['small_options_enable']) && $can_show_atc);

      $btn_html = '';
      if ($can_show_atc) {
        ob_start();
        $GLOBALS['product'] = $product;
        woocommerce_template_loop_add_to_cart();
        $btn_html = ob_get_clean();
      }

      if ($can_show_options && trim(strip_tags($btn_html)) !== '') {
        $open  = !empty($view['small_options_open']) ? '1' : '0';
        $label = esc_html($view['options_label'] ?? 'Options');

        $html .= '<button type="button" class="gwsr-toggle" aria-expanded="'
              .  ($open === '1' ? 'true' : 'false') . '">'
              .  $label
              .  '</button>';
        $html .= '<div class="gwsr-options" ' . ($open === '1' ? '' : 'hidden') . '>';
          $html .= '<div class="gwsr-btn">' . $btn_html . '</div>';
        $html .= '</div>';
      } else {
        if ($can_show_atc && trim(strip_tags($btn_html)) !== '') {
          $html .= '<div class="gwsr-btn">' . $btn_html . '</div>';
        }
      }

    $html .= '</div></article>';

    return $html;
  }
}