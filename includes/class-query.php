<?php
if (!defined('ABSPATH')) exit;

class GWSFB_Query {

  public static function build_args(array $set, array $req): array {
    $per_page_override = isset($req['per_page']) ? (int)$req['per_page'] : 0;
    if ($per_page_override < 0) $per_page_override = 0;

    $per_page = ($per_page_override > 0)
      ? $per_page_override
      : intval($set['results']['per_page'] ?? 12);

    $paged = max(1, intval($req['page'] ?? 1));

    $args = [
      'post_type'      => 'product',
      'post_status'    => 'publish',
      'posts_per_page' => $per_page,
      'paged'          => $paged,
      'tax_query'      => [],
      'meta_query'     => [],
    ];

    $scope = $set['scope'] ?? [];
    if (!is_array($scope)) {
      $scope = [];
    }

    $include_cats = isset($scope['include_cats']) && is_array($scope['include_cats'])
      ? array_values(array_filter(array_map('absint', $scope['include_cats'])))
      : [];

    if (!empty($include_cats)) {
      $args['tax_query'][] = [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => $include_cats,
        'operator' => 'IN',
      ];
    }

    if (!empty($req['cat'])) {
      $cats = array_values(array_filter(array_map('absint', (array) $req['cat'])));
      if ($cats) {
        $args['tax_query'][] = [
          'taxonomy' => 'product_cat',
          'field'    => 'term_id',
          'terms'    => $cats,
          'operator' => 'IN',
        ];
      }
    }

    if (!empty($req['attr']) && is_array($req['attr'])) {
      foreach ($req['attr'] as $tax => $term_ids) {
        $tax = sanitize_key((string) $tax);
        if (!$tax || !taxonomy_exists($tax)) {
          continue;
        }

        $terms = array_values(array_filter(array_map('absint', (array) $term_ids)));
        if (!$terms) {
          continue;
        }

        $args['tax_query'][] = [
          'taxonomy' => $tax,
          'field'    => 'term_id',
          'terms'    => $terms,
          'operator' => 'IN',
        ];
      }
    }

    $min = array_key_exists('min_price', $req) ? $req['min_price'] : null;
    $max = array_key_exists('max_price', $req) ? $req['max_price'] : null;

    if ($min !== null || $max !== null) {
      $minF = ($min !== null && $min !== '') ? floatval($min) : 0.0;
      $maxF = ($max !== null && $max !== '') ? floatval($max) : 999999999.0;

      if ($maxF < $minF) {
        $tmp = $minF; $minF = $maxF; $maxF = $tmp;
      }

      $between = [
        'key'     => '_price',
        'value'   => [$minF, $maxF],
        'compare' => 'BETWEEN',
        'type'    => 'NUMERIC',
      ];

      if ($minF <= 0.0 && $maxF >= 0.0) {
        $args['meta_query'][] = [
          'relation' => 'OR',
          $between,
          [
            'key'     => '_price',
            'compare' => 'NOT EXISTS',
          ],
          [
            'key'     => '_price',
            'value'   => '',
            'compare' => '=',
          ],
        ];
      } else {
        $args['meta_query'][] = $between;
      }
    }

    if (!empty($req['stock']) && $req['stock'] === 'instock') {
      $args['meta_query'][] = [
        'key'   => '_stock_status',
        'value' => 'instock',
      ];
    }

    if (!empty($req['stock_statuses']) && is_array($req['stock_statuses'])) {
      $allowed = ['instock' => true, 'outofstock' => true, 'onbackorder' => true];
      $statuses = [];
      foreach ((array)$req['stock_statuses'] as $s) {
        $k = sanitize_key((string)$s);
        if ($k && isset($allowed[$k])) $statuses[] = $k;
      }
      $statuses = array_values(array_unique($statuses));
      if (!empty($statuses)) {
        $args['meta_query'][] = [
          'key'     => '_stock_status',
          'value'   => $statuses,
          'compare' => 'IN',
        ];
      }
    }

    $orderby = sanitize_key($req['orderby'] ?? '');
    if (!$orderby) {
      $sort_cfg = $set['sort'] ?? [];
      if (is_array($sort_cfg) && !empty($sort_cfg['enabled'])) {
        $orderby = sanitize_key($sort_cfg['default'] ?? 'menu_order');
      }
    }
    if (!$orderby) {
      foreach (($set['filters'] ?? []) as $f) {
        if (($f['type'] ?? '') === 'orderby' && !empty($f['enabled'])) {
          $orderby = sanitize_key($f['default'] ?? 'menu_order');
          break;
        }
      }
    }

    self::apply_orderby($args, $orderby);

    if (!empty($args['meta_query'])) {
      $args['meta_query']['relation'] = 'AND';
    }
    if (!empty($args['tax_query'])) {
      $args['tax_query']['relation'] = 'AND';
    }

    error_log('GWSFB build_args req = ' . print_r($req, true));
    error_log('GWSFB build_args final args = ' . print_r($args, true));

    return $args;
  }

  private static function apply_orderby(array &$args, string $orderby): void {

    if (preg_match('/^attr_(pa_[a-z0-9_]+)__(asc|desc)$/', $orderby, $m)) {
      $args['gwsfb_attr_sort_tax'] = sanitize_key($m[1]);
      $args['gwsfb_attr_sort_dir'] = ($m[2] === 'desc') ? 'DESC' : 'ASC';
      $args['orderby'] = 'title';
      $args['order']   = 'ASC';
      return;
    }

    switch ($orderby) {
      case 'date':
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
        break;

      case 'price':
        $args['meta_key'] = '_price';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'ASC';
        break;

      case 'price-desc':
        $args['meta_key'] = '_price';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;

      case 'popularity':
        $args['meta_key'] = 'total_sales';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;

      case 'rating':
        $args['meta_key'] = '_wc_average_rating';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;

      case 'title':
        $args['orderby'] = 'title';
        $args['order']   = 'ASC';
        break;

      case 'title-desc':
        $args['orderby'] = 'title';
        $args['order']   = 'DESC';
        break;

      case 'menu_order':
      default:
        $args['orderby'] = 'menu_order title';
        $args['order']   = 'ASC';
        break;
    }
  }

  public static function run_query(array $args): WP_Query {

    error_log('GWSFB run_query args = ' . print_r($args, true));

    $tax = isset($args['gwsfb_attr_sort_tax']) ? sanitize_key((string)$args['gwsfb_attr_sort_tax']) : '';
    $dir = isset($args['gwsfb_attr_sort_dir']) ? strtoupper((string)$args['gwsfb_attr_sort_dir']) : 'ASC';
    $dir = ($dir === 'DESC') ? 'DESC' : 'ASC';

    if ($tax && taxonomy_exists($tax)) {
      $cb = function(array $clauses) use ($tax, $dir) {

        global $wpdb;

        $tax_sql = esc_sql($tax);

        $clauses['join'] .= " 
          LEFT JOIN {$wpdb->term_relationships} gwsfb_tr ON ({$wpdb->posts}.ID = gwsfb_tr.object_id)
          LEFT JOIN {$wpdb->term_taxonomy} gwsfb_tt ON (gwsfb_tr.term_taxonomy_id = gwsfb_tt.term_taxonomy_id AND gwsfb_tt.taxonomy = '{$tax_sql}')
          LEFT JOIN {$wpdb->terms} gwsfb_t ON (gwsfb_tt.term_id = gwsfb_t.term_id)
        ";

        $order_expr = "MIN(gwsfb_t.name)";

        if (empty($clauses['groupby'])) {
          $clauses['groupby'] = "{$wpdb->posts}.ID";
        } else {
          if (stripos($clauses['groupby'], "{$wpdb->posts}.ID") === false) {
            $clauses['groupby'] .= ", {$wpdb->posts}.ID";
          }
        }

        $clauses['orderby'] = "
          ({$order_expr} IS NULL) ASC,
          {$order_expr} {$dir},
          {$wpdb->posts}.post_title ASC
        ";

        return $clauses;
      };

      add_filter('posts_clauses', $cb, 20, 1);
      $q = new WP_Query($args);
      remove_filter('posts_clauses', $cb, 20);

      return $q;
    }

    return new WP_Query($args);
  }

  public static function get_available_attribute_terms_map(array $set, array $req): array {

    $attr_taxes = [];

    foreach (($set['filters'] ?? []) as $f) {
      if (($f['type'] ?? '') !== 'attribute') continue;
      if (empty($f['enabled'])) continue;

      $taxes = $f['attributes'] ?? [];
      if (!is_array($taxes)) $taxes = [];

      foreach ($taxes as $tax) {
        $tax = sanitize_key((string)$tax);
        if ($tax && taxonomy_exists($tax)) {
          $attr_taxes[$tax] = true;
        }
      }
    }

    if (empty($attr_taxes)) {
      return [];
    }

    $args = self::build_args($set, $req);
    $args['posts_per_page']         = -1;
    $args['paged']                  = 1;
    $args['fields']                 = 'ids';
    $args['no_found_rows']          = true;
    $args['update_post_meta_cache'] = false;
    $args['update_post_term_cache'] = false;

    $q   = self::run_query($args);
    $ids = $q->posts ?? [];

    if (empty($ids)) {
      $out = [];
      foreach (array_keys($attr_taxes) as $tax) {
        $out[$tax] = [];
      }
      return $out;
    }

    $out = [];
    foreach (array_keys($attr_taxes) as $tax) {
      $out[$tax] = [];
    }

    foreach ($ids as $pid) {
      foreach (array_keys($attr_taxes) as $tax) {
        $term_ids = wp_get_post_terms((int)$pid, $tax, ['fields' => 'ids']);
        if (is_wp_error($term_ids) || empty($term_ids)) continue;

        foreach ($term_ids as $tid) {
          $out[$tax][(int)$tid] = true;
        }
      }
    }

    foreach ($out as $tax => $map) {
      $out[$tax] = array_values(array_map('intval', array_keys($map)));
    }

    return $out;
  }
}