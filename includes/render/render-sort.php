<?php
if (!defined('ABSPATH')) exit;

trait GWSFB_Render_Sort {

  private static function svg_icon_markup(string $type): string {
    $type = sanitize_key($type);

    if ($type === 'list') {
      return '<svg class="gwsfb-results__layout-icon-svg" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>';
    }

    return '<svg class="gwsfb-results__layout-icon-svg" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 4h5v5H4V4zm6 0h5v5h-5V4zm6 0h4v5h-4V4zM4 10h5v5H4v-5zm6 0h5v5h-5v-5zm6 0h4v5h-4v-5zM4 16h5v4H4v-4zm6 0h5v4h-5v-4zm6 0h4v4h-4v-4z"/></svg>';
  }

  private static function normalize_icon_markup(string $html): string {
    $html = trim((string)$html);
    if ($html === '') return '';

    if (stripos($html, '<svg') !== false) {
      $html = preg_replace_callback('/<svg\b([^>]*)>/i', function ($m) {
        $attrs = $m[1] ?? '';
        $attrs = preg_replace('/\swidth\s*=\s*"[^"]*"/i', '', $attrs);
        $attrs = preg_replace('/\sheight\s*=\s*"[^"]*"/i', '', $attrs);

        if (preg_match('/\sclass\s*=\s*"([^"]*)"/i', $attrs, $cm)) {
          $cls = trim($cm[1]);
          if (stripos($cls, 'gwsfb-results__layout-icon-svg') === false) {
            $cls .= ' gwsfb-results__layout-icon-svg';
          }
          $attrs = preg_replace('/\sclass\s*=\s*"[^"]*"/i', ' class="' . esc_attr($cls) . '"', $attrs, 1);
        } else {
          $attrs .= ' class="gwsfb-results__layout-icon-svg"';
        }

        $attrs .= ' width="1em" height="1em"';

        return '<svg' . $attrs . '>';
      }, $html, 1);
    }

    return $html;
  }

  private static function render_layout_icon($icon_setting, string $fallback_type): string {
    if (is_array($icon_setting) && !empty($icon_setting['value']) && class_exists('\Elementor\Icons_Manager')) {
      ob_start();
      try {
        \Elementor\Icons_Manager::render_icon($icon_setting, ['aria-hidden' => 'true']);
      } catch (\Throwable $e) {
      }
      $html = self::normalize_icon_markup((string)ob_get_clean());
      if ($html !== '') {
        return '<span class="gwsfb-results__layout-icon">' . $html . '</span>';
      }
    }

    return '<span class="gwsfb-results__layout-icon">' . self::svg_icon_markup($fallback_type) . '</span>';
  }

  public static function render_layout_switcher_only(array $view): void {
    $group = isset($view['group']) ? (string)$view['group'] : '';

    $layouts = $view['layouts'] ?? [];
    if (!is_array($layouts) || empty($layouts)) return;
    if (count($layouts) < 2) return;

    $current_layout = isset($view['layout']) ? (string)$view['layout'] : '';
    if ($current_layout === '') {
      $current_layout = (string)($layouts[0]['id'] ?? '');
    }

    echo '<div class="gwsfb__layout-switcher gwsfb-results__layout-toggle" role="group" aria-label="Layout" data-group="' . esc_attr($group) . '">';

    foreach ($layouts as $m) {
      if (!is_array($m)) continue;

      $id = isset($m['id']) ? preg_replace('/[^a-zA-Z0-9\_\-]/', '', (string)$m['id']) : '';
      if ($id === '') continue;

      $type = isset($m['type']) ? sanitize_key((string)$m['type']) : 'grid';
      if (!in_array($type, ['grid', 'list'], true)) $type = 'grid';

      $label        = isset($m['label']) && $m['label'] !== '' ? (string)$m['label'] : ($type === 'list' ? 'List' : 'Grid');
      $icon_setting = $m['icon'] ?? null;

      $btn_classes = [
        'gwsfb__layoutbtn',
        'gwsfb-results__layout-btn',
        'elementor-repeater-item-' . $id,
        'gwsfb-results__layout-btn--' . $id,
      ];

      $is_active = ($current_layout === $id);
      if ($is_active) {
        $btn_classes[] = 'is-active';
      }

      echo '<button type="button" class="' . esc_attr(implode(' ', $btn_classes)) . '" data-layout="' . esc_attr($id) . '" aria-pressed="' . ($is_active ? 'true' : 'false') . '" aria-label="' . esc_attr($label) . '">';
        echo self::render_layout_icon($icon_setting, $type === 'list' ? 'list' : 'grid');
      echo '</button>';
    }

    echo '</div>';
  }

  private static function render_sort_bar(array $set, string $current, array $view = []): void {

    $sort_enabled_view = array_key_exists('sort_enabled', $view) ? (!empty($view['sort_enabled']) ? 1 : 0) : 1;
    if (!$sort_enabled_view) return;

    $sort = $set['sort'] ?? [];
    if (!is_array($sort)) $sort = [];

    $builtin_labels = [
      'menu_order' => 'Recommended',
      'date'       => 'Newest',
      'price'      => 'Price: low to high',
      'price-desc' => 'Price: high to low',
      'title'      => 'Name: A to Z',
      'title-desc' => 'Name: Z to A',
      'popularity' => 'Popularity',
      'rating'     => 'Rating',
    ];

    $enabled_builtin = [];
    $bin = $sort['builtin'] ?? [];

    if (is_array($bin)) {
      foreach ($bin as $k => $v) {
        $key = is_int($k) ? sanitize_key((string)$v) : sanitize_key((string)$k);
        $on  = is_int($k) ? 1 : (int)!empty($v);
        if ($key && $on) $enabled_builtin[$key] = true;
      }
    }

    if (empty($enabled_builtin)) {
      $enabled_builtin = [
        'menu_order' => true,
        'price'      => true,
        'price-desc' => true,
        'title'      => true,
      ];
    }

    $attr_sorts = [];
    $attrs = $sort['attributes'] ?? [];
    if (is_array($attrs)) {
      foreach ($attrs as $v) {
        $tax = sanitize_key((string)$v);
        if ($tax && taxonomy_exists($tax)) $attr_sorts[] = $tax;
      }
    }

    $attr_order = $sort['attribute_order'] ?? [];
    if (is_array($attr_order) && !empty($attr_sorts)) {
      usort($attr_sorts, function ($a, $b) use ($attr_order) {
        $oa = isset($attr_order[$a]) ? (int)$attr_order[$a] : 999999;
        $ob = isset($attr_order[$b]) ? (int)$attr_order[$b] : 999999;
        if ($oa === $ob) return strcmp($a, $b);
        return ($oa < $ob) ? -1 : 1;
      });
    }

    $label_show = !empty($view['sort_label_show']) ? 1 : 0;
    $label_text = (isset($view['sort_label_text']) && $view['sort_label_text'] !== '')
      ? (string)$view['sort_label_text']
      : 'Sort';

    $group = isset($view['group']) ? (string)$view['group'] : '';

    $cur = sanitize_key((string)$current);
    if (!$cur) {
      if (!empty($view['sort_default'])) $cur = sanitize_key((string)$view['sort_default']);
      if (!$cur && !empty($sort['default'])) $cur = sanitize_key((string)$sort['default']);
      if (!$cur) $cur = 'menu_order';
    }

    echo '<div class="gwsfb__sort" data-group="' . esc_attr($group) . '">';

    if ($label_show) {
      echo '<span class="gwsfb__sortlabel">' . esc_html($label_text) . '</span>';
    }

    echo '<select name="orderby" class="gwsfb__sortselect">';

    $has_any = false;

    foreach ($builtin_labels as $key => $label) {
      if (empty($enabled_builtin[$key])) continue;
      $has_any = true;
      echo '<option value="' . esc_attr($key) . '" ' . selected($cur, $key, false) . '>' . esc_html($label) . '</option>';
    }

    foreach ($attr_sorts as $tax) {
      $label    = function_exists('wc_attribute_label') ? wc_attribute_label($tax) : $tax;
      $val_asc  = 'attr_' . $tax . '__asc';
      $val_desc = 'attr_' . $tax . '__desc';

      $has_any = true;
      echo '<option value="' . esc_attr($val_asc) . '" ' . selected($cur, $val_asc, false) . '>' . esc_html($label . ' (A-Z)') . '</option>';
      echo '<option value="' . esc_attr($val_desc) . '" ' . selected($cur, $val_desc, false) . '>' . esc_html($label . ' (Z-A)') . '</option>';
    }

    if (!$has_any) {
      echo '<option value="menu_order" selected="selected">Recommended</option>';
    }

    echo '</select>';
    echo '</div>';
  }

  private static function is_attr_orderby(string $orderby): bool {
    return (strpos($orderby, 'attr_') === 0 && strpos($orderby, '__') !== false);
  }

  private static function parse_attr_orderby(string $orderby): array {
    $tax = '';
    $dir = 'asc';

    $parts = explode('__', $orderby);
    if (count($parts) >= 2) {
      $left  = $parts[0];
      $right = $parts[1];
      $tax   = sanitize_key(substr($left, 5));
      $dir   = (strtolower($right) === 'desc') ? 'desc' : 'asc';
    }

    return [$tax, $dir];
  }

  private static function query_with_attr_sort(array $args, string $tax, string $dir): WP_Query {
    global $wpdb;

    if (!$tax || !taxonomy_exists($tax)) {
      return new WP_Query($args);
    }

    $tax_sql = esc_sql($tax);
    $dir_sql = ($dir === 'desc') ? 'DESC' : 'ASC';

    $filter = function ($clauses, $query) use ($wpdb, $tax_sql, $dir_sql) {
      if (!$query instanceof WP_Query) return $clauses;

      if (!$query->get('gwsfb_attr_sort') || $query->get('gwsfb_attr_sort') !== $tax_sql) {
        return $clauses;
      }

      $clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} gwsfb_tr ON {$wpdb->posts}.ID = gwsfb_tr.object_id ";
      $clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} gwsfb_tt ON gwsfb_tr.term_taxonomy_id = gwsfb_tt.term_taxonomy_id AND gwsfb_tt.taxonomy = '{$tax_sql}' ";
      $clauses['join'] .= " LEFT JOIN {$wpdb->terms} gwsfb_t ON gwsfb_tt.term_id = gwsfb_t.term_id ";

      $clauses['groupby'] = "{$wpdb->posts}.ID";

      $base_orderby       = trim((string)($clauses['orderby'] ?? ''));
      $term_order         = "MIN(gwsfb_t.name) {$dir_sql}";
      $clauses['orderby'] = $term_order . ($base_orderby ? ", {$base_orderby}" : '');

      return $clauses;
    };

    add_filter('posts_clauses', $filter, 10, 2);

    $qargs = $args;
    $qargs['gwsfb_attr_sort'] = $tax_sql;

    $q = new WP_Query($qargs);

    remove_filter('posts_clauses', $filter, 10);

    return $q;
  }
}