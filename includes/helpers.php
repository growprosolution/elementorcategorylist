<?php
if (!defined('ABSPATH')) exit;

class GWSFB_Helpers {
  const OPT_KEY = 'gwsfb_filter_sets';

  public static function get_sets(): array {
    $sets = get_option(self::OPT_KEY, []);
    return is_array($sets) ? $sets : [];
  }

  public static function save_sets(array $sets): void {
    update_option(self::OPT_KEY, $sets, false);
  }

  public static function get_set($id): ?array {
    $sets = self::get_sets();
    return isset($sets[$id]) ? $sets[$id] : null;
  }

  public static function new_id(): string {
    return (string) time() . wp_rand(100, 999);
  }

  public static function sanitize_set(array $in): array {
    $out = [];

    $out['name']    = sanitize_text_field($in['name'] ?? 'Untitled');
    $out['enabled'] = !empty($in['enabled']) ? 1 : 0;

    $scope = $in['scope'] ?? [];
    if (!is_array($scope)) $scope = [];

    $include_cats = $scope['include_cats'] ?? [];
    if (!is_array($include_cats)) $include_cats = [];

    $out['scope'] = [
      'include_cats' => array_values(array_filter(array_map('absint', $include_cats))),
    ];

    $filters = $in['filters'] ?? [];
    if (!is_array($filters)) $filters = [];

    $legacy_orderby = null;
    foreach ($filters as $f0) {
      if (!is_array($f0)) continue;
      if (sanitize_key($f0['type'] ?? '') === 'orderby') {
        $legacy_orderby = $f0;
        break;
      }
    }

    $out['filters'] = [];

    foreach ($filters as $f) {
      if (!is_array($f)) continue;

      $type = sanitize_key($f['type'] ?? '');
      if (!$type) continue;

      if ($type === 'category') continue;
      if ($type === 'orderby')  continue;

      $cfg = [
        'type'    => $type,
        'enabled' => !empty($f['enabled']) ? 1 : 0,
      ];

      if ($type === 'attribute') {
        $attrs = $f['attributes'] ?? [];
        if (!is_array($attrs)) $attrs = [];
        $attrs = array_values(array_filter(array_map('sanitize_key', $attrs)));
        $cfg['attributes'] = $attrs;

        $order = $f['attribute_order'] ?? [];
        if (!is_array($order)) $order = [];

        $clean_order = [];
        foreach ($order as $tax => $num) {
          $tax = sanitize_key($tax);
          if (!$tax) continue;
          if (!in_array($tax, $attrs, true)) continue;

          $n = (int)$num;
          if ($n > 0) $clean_order[$tax] = $n;
        }
        $cfg['attribute_order'] = $clean_order;

        $cfg['style'] = in_array(($f['style'] ?? 'checkbox'), ['checkbox', 'select'], true)
          ? $f['style']
          : 'checkbox';

        $cfg['hide_empty'] = !empty($f['hide_empty']) ? 1 : 0;
      }

      if ($type === 'price') {
        $cfg['min']  = floatval($f['min'] ?? 0);
        $cfg['max']  = floatval($f['max'] ?? 0);
        $cfg['step'] = max(1, intval($f['step'] ?? 10));
      }

      if ($type === 'stock') {
        $sts = $f['statuses'] ?? [];
        if (!is_array($sts)) $sts = [];

        $allowed = [
          'instock'     => true,
          'outofstock'  => true,
          'onbackorder' => true,
        ];

        $map = [];
        foreach ($sts as $v) {
          $k = sanitize_key($v);
          if ($k && isset($allowed[$k])) $map[$k] = true;
        }

        $map['instock'] = true;

        $cfg['statuses'] = array_values(array_keys($map));
      }

      $out['filters'][] = $cfg;
    }

    $out['results'] = [
      'per_page'   => max(1, intval($in['results']['per_page'] ?? 12)),
      'columns'    => max(1, min(6, intval($in['results']['columns'] ?? 4))),
      'pagination' => in_array(($in['results']['pagination'] ?? 'ajax'), ['ajax', 'none'], true)
        ? $in['results']['pagination']
        : 'ajax',
    ];

    $allowed_builtin = ['menu_order', 'date', 'price', 'price-desc', 'popularity', 'rating', 'title', 'title-desc'];

    $sort_in = $in['sort'] ?? [];
    if (!is_array($sort_in)) $sort_in = [];

    $enabled = !empty($sort_in['enabled']) ? 1 : 0;
    $def     = sanitize_key($sort_in['default'] ?? 'menu_order');
    if (!$def || !in_array($def, $allowed_builtin, true)) $def = 'menu_order';

    $builtin_in = $sort_in['builtin'] ?? [];
    if (!is_array($builtin_in)) $builtin_in = [];
    $builtin = [];
    foreach ($builtin_in as $k => $v) {
      $key = is_int($k) ? sanitize_key((string)$v) : sanitize_key((string)$k);
      if (!$key || !in_array($key, $allowed_builtin, true)) continue;
      if (!empty($v) || is_int($k)) $builtin[$key] = 1;
    }

    $attr_in = $sort_in['attributes'] ?? [];
    if (!is_array($attr_in)) $attr_in = [];
    $attrs = [];
    foreach ($attr_in as $v) {
      $tax = sanitize_key((string)$v);
      if ($tax && taxonomy_exists($tax)) $attrs[] = $tax;
    }
    $attrs = array_values(array_unique($attrs));

    $attr_order_in = $sort_in['attribute_order'] ?? [];
    if (!is_array($attr_order_in)) $attr_order_in = [];
    $attr_order = [];
    $attr_set = array_flip($attrs);
    foreach ($attr_order_in as $tax => $num) {
      $tax = sanitize_key((string)$tax);
      if (!$tax || !isset($attr_set[$tax])) continue;
      $n = (int)$num;
      if ($n > 0) $attr_order[$tax] = $n;
    }

    if ($legacy_orderby && is_array($legacy_orderby)) {
      $enabled = !empty($legacy_orderby['enabled']) ? 1 : $enabled;

      $d = sanitize_key($legacy_orderby['default'] ?? '');
      if ($d && in_array($d, $allowed_builtin, true)) $def = $d;

      $sb = $legacy_orderby['sort_builtin'] ?? [];
      if (is_array($sb)) {
        foreach ($sb as $k => $v) {
          $key = is_int($k) ? sanitize_key((string)$v) : sanitize_key((string)$k);
          if ($key && in_array($key, $allowed_builtin, true)) {
            if (!empty($v) || is_int($k)) $builtin[$key] = 1;
          }
        }
      }

      $sa = $legacy_orderby['sort_attributes'] ?? [];
      if (is_array($sa)) {
        foreach ($sa as $v) {
          $tax = sanitize_key((string)$v);
          if ($tax && taxonomy_exists($tax)) $attr_set[$tax] = true;
        }
        $attrs = array_values(array_keys($attr_set));
      }

      $sao = $legacy_orderby['sort_attribute_order'] ?? [];
      if (is_array($sao)) {
        foreach ($sao as $tax => $num) {
          $tax = sanitize_key((string)$tax);
          if (!$tax) continue;
          $n = (int)$num;
          if ($n > 0) $attr_order[$tax] = $n;
        }
      }
    }

    $out['sort'] = [
      'enabled'         => $enabled,
      'default'         => $def,
      'builtin'         => $builtin,
      'attributes'      => $attrs,
      'attribute_order' => $attr_order,
    ];

    return $out;
  }
}