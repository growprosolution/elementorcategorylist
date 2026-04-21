<?php
if (!defined('ABSPATH')) exit;

class GWSFB_Ajax {

  public static function init() {
    add_action('wp_ajax_gwsfb_filter', [__CLASS__, 'filter']);
    add_action('wp_ajax_nopriv_gwsfb_filter', [__CLASS__, 'filter']);
  }

  private static function compute_per_page_from_view(array $view): int {
    $rows = 0;
    $cols = 0;
    $type = '';
    $device = '';

    if (isset($view['layout_type'])) {
      $type = sanitize_key((string)$view['layout_type']);
    }

    if (isset($view['responsive_device'])) {
      $device = sanitize_key((string)$view['responsive_device']);
      if (!in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
        $device = '';
      }
    }

    if ($device !== '') {
      $rows_key = 'rows_' . $device;
      $cols_key = 'columns_' . $device;

      if (isset($view[$rows_key])) {
        $rows = max(0, (int)$view[$rows_key]);
      }
      if (isset($view[$cols_key])) {
        $cols = max(1, (int)$view[$cols_key]);
      }
    }

    if ($rows <= 0 && isset($view['rows'])) {
      $rows = max(0, (int)$view['rows']);
    }

    if ($cols <= 0 && isset($view['columns'])) {
      $cols = max(1, (int)$view['columns']);
    }

    if ($rows <= 0) return 0;

    if ($type === 'list') {
      return $rows;
    }

    return $rows * max(1, $cols);
  }

  private static function sanitize_stock_statuses($raw): array {
    $allowed = ['instock' => true, 'outofstock' => true, 'onbackorder' => true];
    $out = [];

    if (!is_array($raw)) return [];

    foreach ($raw as $s) {
      $k = sanitize_key((string)$s);
      if ($k && isset($allowed[$k])) $out[$k] = true;
    }

    return array_values(array_keys($out));
  }

  private static function get_enabled_filter_types(array $set): array {
    $map = [
      'attribute' => false,
      'price'     => false,
      'stock'     => false,
      'category'  => false,
    ];

    foreach (($set['filters'] ?? []) as $f) {
      if (!is_array($f) || empty($f['enabled'])) continue;
      $t = sanitize_key($f['type'] ?? '');
      if ($t && array_key_exists($t, $map)) {
        $map[$t] = true;
      }
    }

    return $map;
  }

  private static function get_allowed_attr_taxes(array $set): array {
    $out = [];
    foreach (($set['filters'] ?? []) as $f) {
      if (!is_array($f) || empty($f['enabled'])) continue;
      if (sanitize_key($f['type'] ?? '') !== 'attribute') continue;

      $attrs = $f['attributes'] ?? [];
      if (!is_array($attrs)) $attrs = [];

      foreach ($attrs as $tax) {
        $tax = sanitize_key((string)$tax);
        if ($tax && taxonomy_exists($tax)) {
          $out[$tax] = true;
        }
      }
    }
    return $out;
  }

  private static function get_default_stock_statuses_from_set(array $set): array {
    foreach (($set['filters'] ?? []) as $f) {
      if (!is_array($f) || empty($f['enabled'])) continue;
      if (sanitize_key($f['type'] ?? '') !== 'stock') continue;

      $sts = $f['statuses'] ?? [];
      if (!is_array($sts)) $sts = [];

      $sts = self::sanitize_stock_statuses($sts);
      if (empty($sts)) $sts = ['instock'];

      return $sts;
    }

    return [];
  }

  public static function filter() {
    check_ajax_referer('gwsfb_filter', 'nonce');

    $id  = sanitize_text_field($_POST['set_id'] ?? '');
    $set = GWSFB_Helpers::get_set($id);

    if (!$set || empty($set['enabled'])) {
      wp_send_json_error(['message' => 'Invalid set']);
    }

    $group = sanitize_key((string)($_POST['group'] ?? ''));

    $req = isset($_POST['req']) && is_array($_POST['req']) ? wp_unslash($_POST['req']) : [];
    if (!is_array($req)) $req = [];

    $view = isset($_POST['view']) && is_array($_POST['view']) ? wp_unslash($_POST['view']) : [];
    if (!is_array($view)) $view = [];

    $view = GWSFB_Render::sanitize_view($view);

    $clean = [
      'group'   => $group,
      'page'    => max(1, (int)($req['page'] ?? 1)),
      'orderby' => sanitize_key($req['orderby'] ?? ''),
    ];

    $enabled = self::get_enabled_filter_types($set);

    if (!empty($req['cat'])) {
      $cats = array_values(array_filter(array_map('absint', (array)$req['cat'])));
      if ($cats) $clean['cat'] = $cats;
    }

    if (!empty($enabled['attribute'])) {
      $allowed_attr = self::get_allowed_attr_taxes($set);

      if (!empty($req['attr']) && is_array($req['attr']) && !empty($allowed_attr)) {
        $attr_out = [];

        foreach ($req['attr'] as $tax => $term_ids) {
          $tax = sanitize_key((string)$tax);
          if (!$tax) continue;
          if (empty($allowed_attr[$tax])) continue;

          $terms = array_values(array_filter(array_map('absint', (array)$term_ids)));
          if (!$terms) continue;

          $attr_out[$tax] = $terms;
        }

        if (!empty($attr_out)) {
          $clean['attr'] = $attr_out;
        }
      }
    }

    $min_present = $enabled['price'] && array_key_exists('min_price', $req) && $req['min_price'] !== '' && $req['min_price'] !== null;
    $max_present = $enabled['price'] && array_key_exists('max_price', $req) && $req['max_price'] !== '' && $req['max_price'] !== null;

    if ($min_present) $clean['min_price'] = (float)$req['min_price'];
    if ($max_present) $clean['max_price'] = (float)$req['max_price'];

    $force_no_results = false;

    if (!empty($enabled['stock'])) {
      $defaults = self::get_default_stock_statuses_from_set($set);

      if (array_key_exists('stock_statuses', $req)) {
        $statuses = self::sanitize_stock_statuses($req['stock_statuses']);

        if (empty($statuses)) {
          $force_no_results = true;
        } else {
          if (count($statuses) === 1 && $statuses[0] === 'instock') {
            $clean['stock'] = 'instock';
          } else {
            $clean['stock_statuses'] = $statuses;
          }
        }
      } elseif (!empty($req['stock']) && sanitize_key((string)$req['stock']) === 'instock') {
        $clean['stock'] = 'instock';
      } else {
        if (!empty($defaults)) {
          if (count($defaults) === 1 && $defaults[0] === 'instock') {
            $clean['stock'] = 'instock';
          } else {
            $clean['stock_statuses'] = $defaults;
          }
        }
      }
    } else {
      if (array_key_exists('stock_statuses', $req)) {
        $statuses = self::sanitize_stock_statuses($req['stock_statuses']);
        if (!empty($statuses)) {
          if (count($statuses) === 1 && $statuses[0] === 'instock') {
            $clean['stock'] = 'instock';
          } else {
            $clean['stock_statuses'] = $statuses;
          }
        }
      } elseif (!empty($req['stock']) && sanitize_key((string)$req['stock']) === 'instock') {
        $clean['stock'] = 'instock';
      }
    }

    $ppo = 0;
    if (isset($view['per_page_override'])) {
      $ppo = max(0, (int)$view['per_page_override']);
    }
    if ($ppo <= 0) {
      $ppo = self::compute_per_page_from_view($view);
    }
    if ($ppo > 0) {
      $clean['per_page'] = $ppo;
    }

    $need_price_zero = false;
    if ($enabled['price'] && ($min_present || $max_present)) {
      $minF = $min_present ? (float)$clean['min_price'] : 0.0;
      $maxF = $max_present ? (float)$clean['max_price'] : 999999999.0;

      if ($maxF < $minF) {
        $tmp = $minF; $minF = $maxF; $maxF = $tmp;
      }

      if ($minF <= 0.0 && $maxF >= 0.0) {
        $need_price_zero = true;
      }
    }

    $price_clause_filter = null;
    $force_empty_where_filter = null;

    if ($force_no_results) {
      $force_empty_where_filter = function ($where, $query) {
        if (!$query instanceof WP_Query) return $where;

        $pt = $query->get('post_type');
        if (is_array($pt)) {
          if (!in_array('product', $pt, true)) return $where;
        } else {
          if ($pt && $pt !== 'product') return $where;
        }

        return $where . ' AND 1=0 ';
      };
      add_filter('posts_where', $force_empty_where_filter, 9, 2);
    }

    if ($need_price_zero) {
      global $wpdb;

      $price_clause_filter = function ($clauses, $query) use ($wpdb) {
        if (!$query instanceof WP_Query) return $clauses;

        $pt = $query->get('post_type');
        if (is_array($pt)) {
          if (!in_array('product', $pt, true)) return $clauses;
        } else {
          if ($pt && $pt !== 'product') return $clauses;
        }

        if (empty($clauses['where']) || stripos($clauses['where'], "_price") === false) {
          return $clauses;
        }

        if (!preg_match("/\\b(mt\\d+)\\.meta_key\\s*=\\s*'\\_price'/i", (string)$clauses['where'], $m)) {
          return $clauses;
        }

        $alias = $m[1];

        $pm    = $wpdb->postmeta;
        $posts = $wpdb->posts;

        $join_re = '/INNER\\s+JOIN\\s+' . preg_quote($pm, '/') . '\\s+AS\\s+' . preg_quote($alias, '/') . '\\s+ON\\s*\\(\\s*' . preg_quote($posts, '/') . '\\.ID\\s*=\\s*' . preg_quote($alias, '/') . '\\.post_id\\s*\\)/i';
        $join_rep = 'LEFT JOIN ' . $pm . ' AS ' . $alias . ' ON (' . $posts . '.ID = ' . $alias . ".post_id AND " . $alias . ".meta_key = '_price')";
        $clauses['join'] = preg_replace($join_re, $join_rep, (string)$clauses['join'], 1);

        $clauses['where'] = preg_replace(
          "/\\b" . preg_quote($alias, '/') . "\\.meta_key\\s*=\\s*'\\_price'\\s+AND\\s+/i",
          '',
          (string)$clauses['where'],
          1
        );

        $pattern1 = '/\\(\\s*CAST\\(\\s*' . preg_quote($alias, '/') . '\\.meta_value\\s+AS\\s+[^\\)]+\\)\\s+BETWEEN\\s+[^\\)]+?\\s+AND\\s+[^\\)]+?\\s*\\)/i';
        $clauses['where'] = preg_replace_callback($pattern1, function ($mm) use ($alias) {
          $expr = trim($mm[0]);
          $expr = preg_replace('/^\\(\\s*/', '', $expr);
          $expr = preg_replace('/\\s*\\)$/', '', $expr);
          return '((' . $expr . ') OR ' . $alias . '.post_id IS NULL OR ' . $alias . ".meta_value = '')";
        }, (string)$clauses['where'], 1, $replaced);

        if (empty($replaced)) {
          $pattern2 = '/CAST\\(\\s*' . preg_quote($alias, '/') . '\\.meta_value\\s+AS\\s+[^\\)]+\\)\\s+BETWEEN\\s+[^ \\)]+\\s+AND\\s+[^ \\)]+/i';
          $clauses['where'] = preg_replace_callback($pattern2, function ($mm) use ($alias) {
            return '((' . $mm[0] . ') OR ' . $alias . '.post_id IS NULL OR ' . $alias . ".meta_value = '')";
          }, (string)$clauses['where'], 1);
        }

        return $clauses;
      };

      add_filter('posts_clauses', $price_clause_filter, 9, 2);
    }

    ob_start();
    GWSFB_Render::render_results($set, $clean, $view);
    $html = ob_get_clean();

    $calc_terms = isset($_POST['calc_terms']) ? (int)$_POST['calc_terms'] : 1;

    $payload = [
      'html' => $html,
    ];

    if ($calc_terms) {
      $payload['available_terms'] = GWSFB_Query::get_available_attribute_terms_map($set, $clean);
    }

    if ($price_clause_filter) {
      remove_filter('posts_clauses', $price_clause_filter, 9);
    }
    if ($force_empty_where_filter) {
      remove_filter('posts_where', $force_empty_where_filter, 9);
    }

    wp_send_json_success($payload);
  }
}