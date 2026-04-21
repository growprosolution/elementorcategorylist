<?php
if (!defined('ABSPATH')) exit;

class GWSFB_Admin {

  public static function init() {
    add_action('admin_menu', [__CLASS__, 'menu']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
    add_action('admin_post_gwsfb_save_set', [__CLASS__, 'save_set']);
    add_action('admin_post_gwsfb_delete_set', [__CLASS__, 'delete_set']);
  }

  public static function menu() {
    add_submenu_page(
      'woocommerce',
      'Filter Builder',
      'Filter Builder',
      'manage_woocommerce',
      'gwsfb-filter-builder',
      [__CLASS__, 'page']
    );
  }

  public static function assets($hook) {
    if (strpos($hook, 'gwsfb-filter-builder') === false) {
      return;
    }

    wp_enqueue_style('gwsfb-admin', GWSFB_URL . 'assets/admin.css', [], GWSFB_VER);
    wp_enqueue_script('gwsfb-admin', GWSFB_URL . 'assets/admin.js', ['jquery'], GWSFB_VER, true);
  }

  public static function page() {
    if (!current_user_can('manage_woocommerce')) {
      return;
    }

    $sets    = GWSFB_Helpers::get_sets();
    $edit_id = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '';
    $editing = $edit_id && isset($sets[$edit_id]) ? $sets[$edit_id] : null;

    echo '<div class="wrap gwsfb-admin-wrap">';
    echo '<h1 class="gwsfb-page-title">GWS Filter Builder</h1>';

    echo '<div class="gwsfb-card gwsfb-card--list">';
    echo '<div class="gwsfb-card__header">';
    echo '<h2 class="gwsfb-card__title">Filter Sets</h2>';
    echo '</div>';

    echo '<div class="gwsfb-card__body">';
    echo '<table class="widefat striped gwsfb-sets-table"><thead>
            <tr>
              <th>Name</th>
              <th>Status</th>
              <th>Shortcode</th>
              <th style="width:170px;">Actions</th>
            </tr>
          </thead><tbody>';

    if ($sets) {
      foreach ($sets as $id => $set) {
        $name    = esc_html($set['name'] ?? '');
        $enabled = !empty($set['enabled']) ? 'Enabled' : 'Disabled';
        $sc      = '[gws_filters id="' . esc_attr($id) . '"]';

        $edit_url = admin_url('admin.php?page=gwsfb-filter-builder&edit=' . urlencode($id));
        $del_url  = wp_nonce_url(
          admin_url('admin-post.php?action=gwsfb_delete_set&id=' . urlencode($id)),
          'gwsfb_delete_set'
        );

        echo '<tr>';
        echo '<td>' . $name . '</td>';
        echo '<td><span class="gwsfb-status gwsfb-status--' . (!empty($set['enabled']) ? 'on' : 'off') . '">' . $enabled . '</span></td>';
        echo '<td><code>' . esc_html($sc) . '</code></td>';
        echo '<td class="gwsfb-sets-table__actions">
                <a class="button button-small" href="' . esc_url($edit_url) . '">Edit</a>
                <a class="button button-small button-link-delete" href="' . esc_url($del_url) . '" onclick="return confirm(\'Delete this set?\')">Delete</a>
              </td>';
        echo '</tr>';
      }
    } else {
      echo '<tr><td colspan="4">No filter sets yet.</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    echo '</div>';

    $is_new = !$editing;
    $id     = $is_new ? '' : $edit_id;

    $defaults = [
      'name'    => '',
      'enabled' => 1,

      'filters' => [
        [
          'type'            => 'attribute',
          'enabled'         => 1,
          'attributes'      => [],
          'attribute_order' => [],
          'style'           => 'checkbox',
          'hide_empty'      => 1,
        ],
        [
          'type'    => 'price',
          'enabled' => 1,
          'min'     => 0,
          'max'     => 0,
          'step'    => 10,
        ],
        [
          'type'     => 'stock',
          'enabled'  => 1,
          'statuses' => ['instock'],
        ],
      ],

      'sort' => [
        'enabled'         => 1,
        'default'         => 'menu_order',
        'builtin'         => ['menu_order' => 1, 'price' => 1, 'price-desc' => 1, 'title' => 1],
        'attributes'      => [],
        'attribute_order' => [],
      ],

      'results' => [
        'per_page'   => 12,
        'columns'    => 4,
        'pagination' => 'ajax',
      ],

      'scope' => [
        'include_cats' => [],
        'exclude_cats' => [],
      ],
    ];

    $set = $editing ? $editing : $defaults;

    if (!isset($set['filters']) || !is_array($set['filters'])) {
      $set['filters'] = $defaults['filters'];
    }
    if (!isset($set['results']) || !is_array($set['results'])) {
      $set['results'] = $defaults['results'];
    }
    if (!isset($set['scope']) || !is_array($set['scope'])) {
      $set['scope'] = $defaults['scope'];
    }
    if (!isset($set['sort']) || !is_array($set['sort'])) {
      $set['sort'] = $defaults['sort'];
    }

    $legacy_orderby = null;
    $set['filters'] = array_values(array_filter($set['filters'], function ($f) use (&$legacy_orderby) {
      if (!is_array($f)) return false;
      $type = $f['type'] ?? '';
      if ($type === 'category') return false;
      if ($type === 'orderby') {
        $legacy_orderby = $f;
        return false;
      }
      return true;
    }));

    if ($legacy_orderby && is_array($legacy_orderby)) {
      $allowed = ['menu_order', 'date', 'price', 'price-desc', 'popularity', 'rating', 'title', 'title-desc'];

      if (!isset($set['sort']['default']) || !is_string($set['sort']['default'])) {
        $set['sort']['default'] = 'menu_order';
      }

      $d = sanitize_key($legacy_orderby['default'] ?? '');
      if ($d && in_array($d, $allowed, true)) {
        $set['sort']['default'] = $d;
      }

      $sb = $legacy_orderby['sort_builtin'] ?? [];
      $clean_builtin = [];
      if (is_array($sb)) {
        foreach ($sb as $k => $v) {
          $key = is_int($k) ? $v : $k;
          $key = sanitize_key($key);
          if (!$key || !in_array($key, $allowed, true)) continue;
          if (!empty($v) || is_int($k)) $clean_builtin[$key] = 1;
        }
      }
      if ($clean_builtin) {
        $set['sort']['builtin'] = $clean_builtin;
      }

      $sa = $legacy_orderby['sort_attributes'] ?? [];
      if (is_array($sa)) {
        $clean_sa = array_values(array_filter(array_map('sanitize_key', $sa)));
        $set['sort']['attributes'] = $clean_sa;
      }

      $sao = $legacy_orderby['sort_attribute_order'] ?? [];
      if (is_array($sao)) {
        $clean_sao = [];
        foreach ($sao as $tax => $num) {
          $tax = sanitize_key($tax);
          if (!$tax) continue;
          $n = (int) $num;
          if ($n > 0) $clean_sao[$tax] = $n;
        }
        $set['sort']['attribute_order'] = $clean_sao;
      }
    }

    foreach ($set['filters'] as $fi => $ff) {
      if (($ff['type'] ?? '') !== 'stock') continue;

      $sts = $ff['statuses'] ?? [];
      if (!is_array($sts)) $sts = [];

      $map = [];
      foreach ($sts as $v) {
        $k = sanitize_key($v);
        if ($k) $map[$k] = true;
      }

      $map['instock'] = true;

      $allowed = ['instock' => true, 'outofstock' => true, 'onbackorder' => true];
      foreach ($map as $k => $v) {
        if (!isset($allowed[$k])) unset($map[$k]);
      }

      $set['filters'][$fi]['statuses'] = array_values(array_keys($map));
      break;
    }

    $sort_allowed = ['menu_order', 'date', 'price', 'price-desc', 'popularity', 'rating', 'title', 'title-desc'];

    $set['sort']['enabled'] = !empty($set['sort']['enabled']) ? 1 : 0;

    $sd = sanitize_key($set['sort']['default'] ?? 'menu_order');
    if (!$sd || !in_array($sd, $sort_allowed, true)) $sd = 'menu_order';
    $set['sort']['default'] = $sd;

    $builtin = $set['sort']['builtin'] ?? [];
    if (!is_array($builtin)) $builtin = [];
    $clean_builtin = [];
    foreach ($builtin as $k => $v) {
      $key = is_int($k) ? $v : $k;
      $key = sanitize_key($key);
      if (!$key || !in_array($key, $sort_allowed, true)) continue;
      if (!empty($v) || is_int($k)) $clean_builtin[$key] = 1;
    }
    if (empty($clean_builtin)) {
      $clean_builtin = $defaults['sort']['builtin'];
    }
    $set['sort']['builtin'] = $clean_builtin;

    $sort_attrs = $set['sort']['attributes'] ?? [];
    if (!is_array($sort_attrs)) $sort_attrs = [];
    $sort_attr_map = [];
    foreach ($sort_attrs as $v) {
      $k = sanitize_key($v);
      if ($k) $sort_attr_map[$k] = true;
    }

    $sort_attr_order = $set['sort']['attribute_order'] ?? [];
    if (!is_array($sort_attr_order)) $sort_attr_order = [];
    $sort_attr_order_map = [];
    foreach ($sort_attr_order as $tax => $num) {
      $tax = sanitize_key($tax);
      if (!$tax) continue;
      if (!isset($sort_attr_map[$tax])) continue;
      $n = (int) $num;
      if ($n > 0) $sort_attr_order_map[$tax] = $n;
    }
    $set['sort']['attribute_order'] = $sort_attr_order_map;

    $scope_include = isset($set['scope']['include_cats']) && is_array($set['scope']['include_cats'])
      ? array_map('intval', $set['scope']['include_cats'])
      : [];

    echo '<div class="gwsfb-card gwsfb-card--editor">';
    echo '<div class="gwsfb-card__header">';
    echo '<h2 class="gwsfb-card__title">' . ($is_new ? 'Create New Filter' : 'Edit Filter') . '</h2>';
    echo '</div>';

    echo '<div class="gwsfb-card__body">';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="gwsfb-set-form">';
    echo '<input type="hidden" name="action" value="gwsfb_save_set">';
    wp_nonce_field('gwsfb_save_set');
    echo '<input type="hidden" name="id" value="' . esc_attr($id) . '">';

    echo '<table class="form-table gwsfb-form-table"><tbody>';

    echo '<tr>
      <th scope="row"><label for="gwsfb_set_name">Name</label></th>
      <td>
        <input id="gwsfb_set_name" type="text" name="set[name]" value="' . esc_attr($set['name']) . '" class="regular-text">
        <p class="description">Internal label for this filter set. This will not be shown on the front end.</p>
      </td>
    </tr>';

    echo '<tr>
      <th scope="row">Status</th>
      <td>
        <label class="gwsfb-switch">
          <input type="checkbox" name="set[enabled]" value="1" ' . checked(!empty($set['enabled']), true, false) . '>
          <span class="gwsfb-switch__slider"></span>
        </label>
        <span class="gwsfb-switch__label">Enable this set</span>
      </td>
    </tr>';

    echo '<tr>
      <th scope="row">Product Scope</th>
      <td>';

    echo '<p class="description">Select one or more base categories. All filters and sorting will only apply to products inside these categories. If you leave this empty, the set will use all products.</p>';

    $cats = get_terms([
      'taxonomy'   => 'product_cat',
      'hide_empty' => false,
      'orderby'    => 'name',
      'order'      => 'ASC',
      'parent'     => 0,
    ]);

    if (!is_wp_error($cats) && !empty($cats)) {
      echo '<div class="gwsfb-scope-panel">';
      echo '<div class="gwsfb-scope-panel__header">';
      echo '<h4 class="gwsfb-scope-panel__title">Include categories</h4>';
      echo '</div>';

      echo '<div class="gwsfb-scope-panel__body">';
      echo '<div class="gwsfb-cat-list" style="column-width:240px; column-gap:18px; max-width:100%;">';

      foreach ($cats as $term) {
        self::render_scope_cat_row($term, $scope_include, 'set[scope][include_cats][]', 0);
      }

      echo '</div>';
      echo '</div>';
      echo '</div>';
    } else {
      echo '<p class="description">No product categories found.</p>';
    }

    echo '</td></tr>';

    echo '<tr><th scope="row" colspan="2">Filters</th></tr>';
    echo '<tr><td colspan="2">';
    echo '<div class="gwsfb-filters-list">';

    foreach ($set['filters'] as $i => $f) {
      $type       = $f['type'] ?? '';
      $type_label = ucfirst($type);

      echo '<div class="gwsfb-filter-box gwsfb-filter-box--' . esc_attr($type) . '">';

      echo '<div class="gwsfb-filter-box__header">';
      echo '<span class="gwsfb-badge gwsfb-badge--type">' . esc_html($type_label) . '</span>';
      echo '<label class="gwsfb-filter-enabled">';
      echo '<input type="checkbox" name="set[filters][' . $i . '][enabled]" value="1" ' . checked(!empty($f['enabled']), true, false) . '>';
      echo '<span>Enabled</span>';
      echo '</label>';
      echo '</div>';

      echo '<input type="hidden" name="set[filters][' . $i . '][type]" value="' . esc_attr($type) . '">';

      echo '<div class="gwsfb-filter-box__body">';

      if ($type === 'attribute') {
        $all = wc_get_attribute_taxonomies();

        echo '<div class="gwsfb-subtitle">Attributes</div>';

        $selected_for_filter = is_array($f['attributes'] ?? null) ? $f['attributes'] : [];
        $filter_attr_map = [];
        foreach ($selected_for_filter as $attr_val) {
          $tax = sanitize_key($attr_val);
          if ($tax) $filter_attr_map[$tax] = true;
        }

        $filter_order_map = [];
        if (!empty($f['attribute_order']) && is_array($f['attribute_order'])) {
          foreach ($f['attribute_order'] as $tax => $num) {
            $tax = sanitize_key($tax);
            if (!$tax) continue;
            if (!isset($filter_attr_map[$tax])) continue;
            $n = (int) $num;
            if ($n > 0) $filter_order_map[$tax] = $n;
          }
        }

        if ($all) {
          echo '<div class="gwsfb-attr-table">';
          echo '<div class="gwsfb-attr-table__head">
                  <div class="gwsfb-attr-table__col-label">Attribute</div>
                  <div class="gwsfb-attr-table__col-toggle">Filter</div>
                  <div class="gwsfb-attr-table__col-toggle">Sort</div>
                </div>';

          foreach ($all as $a) {
            $tax  = sanitize_key('pa_' . $a->attribute_name);
            $name = $a->attribute_label;

            $in_filter = !empty($filter_attr_map[$tax]);
            $in_sort   = !empty($sort_attr_map[$tax]);

            $filter_ord = isset($filter_order_map[$tax]) ? (int) $filter_order_map[$tax] : '';
            $sort_ord   = isset($sort_attr_order_map[$tax]) ? (int) $sort_attr_order_map[$tax] : '';

            echo '<div class="gwsfb-attr-table__row">';
            echo '<div class="gwsfb-attr-table__col-label"><strong>' . esc_html($name) . '</strong></div>';

            echo '<div class="gwsfb-attr-table__col-toggle" style="gap:8px;">';
            echo '  <label class="gwsfb-checkbox-pill" style="margin:0;">
                      <input type="checkbox" name="set[filters][' . $i . '][attributes][]" value="' . esc_attr($tax) . '" ' . checked($in_filter, true, false) . '>
                      <span>Filter</span>
                    </label>';
            echo '  <input
                      class="gwsfb-order-input"
                      type="number"
                      min="1"
                      step="1"
                      inputmode="numeric"
                      placeholder="#"
                      name="set[filters][' . $i . '][attribute_order][' . esc_attr($tax) . ']"
                      value="' . esc_attr($filter_ord) . '"
                      style="width:64px; height:28px;"
                    />';
            echo '</div>';

            echo '<div class="gwsfb-attr-table__col-toggle" style="gap:8px;">';
            echo '  <label class="gwsfb-checkbox-pill" style="margin:0;">
                      <input type="checkbox" name="set[sort][attributes][]" value="' . esc_attr($tax) . '" ' . checked($in_sort, true, false) . '>
                      <span>Sort</span>
                    </label>';
            echo '  <input
                      class="gwsfb-order-input"
                      type="number"
                      min="1"
                      step="1"
                      inputmode="numeric"
                      placeholder="#"
                      name="set[sort][attribute_order][' . esc_attr($tax) . ']"
                      value="' . esc_attr($sort_ord) . '"
                      style="width:64px; height:28px;"
                    />';
            echo '</div>';

            echo '</div>';
          }

          echo '</div>';
          echo '<p class="description">Order numbers control display priority. Lower numbers appear first. Leave blank to use default order.</p>';
        } else {
          echo '<p class="description">No global attributes found.</p>';
        }

        echo '<input type="hidden" name="set[filters][' . $i . '][style]" value="checkbox">';

        echo '<div class="gwsfb-field-row">';
        echo '<div class="gwsfb-field gwsfb-field--inline-checkbox">';
        echo '<label class="gwsfb-checkbox-inline">
                <input type="checkbox" name="set[filters][' . $i . '][hide_empty]" value="1" ' . checked(!empty($f['hide_empty']), true, false) . '>
                Hide terms with no products
              </label>';
        echo '</div>';
        echo '</div>';
      }

      if ($type === 'price') {
        echo '<div class="gwsfb-field-row gwsfb-field-row--split">';
        echo '<div class="gwsfb-field">
                <label>Step</label>
                <input type="number" name="set[filters][' . $i . '][step]" value="' . esc_attr($f['step']) . '" class="small-text">
              </div>';
        echo '</div>';
        echo '<p class="description">Price min and max are calculated automatically from the products in the current scope. Step controls the slider interval.</p>';
      }

      if ($type === 'stock') {
        $saved = $f['statuses'] ?? [];
        if (!is_array($saved)) $saved = [];

        $map = [];
        foreach ($saved as $v) {
          $k = sanitize_key($v);
          if ($k) $map[$k] = true;
        }
        $map['instock'] = true;

        echo '<div class="gwsfb-subtitle">Stock status</div>';
        echo '<div class="gwsfb-field-row">';
        echo '<div class="gwsfb-pill-grid">';

        echo '<input type="hidden" name="set[filters][' . $i . '][statuses][]" value="instock">';
        echo '<span class="gwsfb-stock-label gwsfb-stock-label--required">In stock (required)</span>';

        $optional = [
          'outofstock'  => 'Out of stock',
          'onbackorder' => 'On backorder',
        ];

        foreach ($optional as $k => $label) {
          $checked = !empty($map[$k]);
          echo '<label class="gwsfb-checkbox-pill">
                  <input type="checkbox" name="set[filters][' . $i . '][statuses][]" value="' . esc_attr($k) . '" ' . checked($checked, true, false) . '>
                  <span>' . esc_html($label) . '</span>
                </label>';
        }

        echo '</div>';
        echo '<p class="description">Products must match one of the selected statuses. In stock is always included.</p>';
        echo '</div>';
      }

      echo '</div>';
      echo '</div>';
    }

    echo '</div>';
    echo '</td></tr>';

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

    echo '<tr><th scope="row" colspan="2">Sorting</th></tr>';
    echo '<tr><td colspan="2">';
    echo '<div class="gwsfb-filter-box gwsfb-filter-box--sort">';

    echo '<div class="gwsfb-filter-box__header">';
    echo '<span class="gwsfb-badge gwsfb-badge--type">Sort</span>';
    echo '<label class="gwsfb-filter-enabled">';
    echo '<input type="checkbox" name="set[sort][enabled]" value="1" ' . checked(!empty($set['sort']['enabled']), true, false) . '>';
    echo '<span>Enabled</span>';
    echo '</label>';
    echo '</div>';

    echo '<div class="gwsfb-filter-box__body">';

    echo '<div class="gwsfb-field-row">';
    echo '<div class="gwsfb-field">';
    echo '<label>Default order</label>';
    echo '<select name="set[sort][default]">';
    foreach ($builtin_labels as $key => $label) {
      echo '<option value="' . esc_attr($key) . '" ' . selected($set['sort']['default'], $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Initial sort option when no explicit selection is made.</p>';
    echo '</div>';
    echo '</div>';

    echo '<div class="gwsfb-field-row">';
    echo '<div class="gwsfb-field">';
    echo '<label>Enabled sort options</label>';
    echo '<div class="gwsfb-pill-grid">';
    foreach ($builtin_labels as $key => $label) {
      $checked = !empty($set['sort']['builtin'][$key]);
      echo '<label class="gwsfb-checkbox-pill">
              <input type="checkbox" name="set[sort][builtin][' . esc_attr($key) . ']" value="1" ' . checked($checked, true, false) . '>
              <span>' . esc_html($label) . '</span>
            </label>';
    }
    echo '</div>';
    echo '<p class="description">Attribute-based sort options are selected per-attribute in the Attributes table above.</p>';
    echo '</div>';
    echo '</div>';

    echo '</div>';
    echo '</div>';
    echo '</td></tr>';

    echo '</tbody></table>';

    submit_button($is_new ? 'Create Filter' : 'Save Changes', 'primary gwsfb-submit');

    echo '</form>';

    echo '<p class="gwsfb-help-text"><strong>Elementor:</strong> Add the “GWS Product Filter” widget and the “GWS Products Results” widget. Use the same Filter Set ID and Group Key so filters, sorting, and pagination work together.</p>';

    echo '</div>';
    echo '</div>';

    echo '</div>';
  }

  public static function save_set() {
    if (!current_user_can('manage_woocommerce')) {
      wp_die('No permission');
    }

    check_admin_referer('gwsfb_save_set');

    $id  = sanitize_text_field($_POST['id'] ?? '');
    $set = $_POST['set'] ?? [];
    if (!is_array($set)) {
      $set = [];
    }

    $sets = GWSFB_Helpers::get_sets();

    if (!$id) {
      $id = GWSFB_Helpers::new_id();
    }

    $sets[$id] = GWSFB_Helpers::sanitize_set($set);
    GWSFB_Helpers::save_sets($sets);

    wp_redirect(admin_url('admin.php?page=gwsfb-filter-builder&saved=1'));
    exit;
  }

  public static function delete_set() {
    if (!current_user_can('manage_woocommerce')) {
      wp_die('No permission');
    }

    check_admin_referer('gwsfb_delete_set');

    $id   = sanitize_text_field($_GET['id'] ?? '');
    $sets = GWSFB_Helpers::get_sets();

    if ($id && isset($sets[$id])) {
      unset($sets[$id]);
      GWSFB_Helpers::save_sets($sets);
    }

    wp_redirect(admin_url('admin.php?page=gwsfb-filter-builder'));
    exit;
  }

  protected static function render_scope_cat_row($term, array $selected_ids, string $field_name, int $level = 0) {
    if (!($term instanceof WP_Term)) {
      return;
    }

    $checked = in_array((int) $term->term_id, $selected_ids, true);
    $indent  = max(0, $level) * 16;

    echo '<div class="gwsfb-cat-tree-node" style="margin-left:' . (int) $indent . 'px; break-inside:avoid;">';
    echo '<label class="gwsfb-checkbox-inline gwsfb-checkbox-inline--cat" style="display:block;margin:2px 0;">';
    echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="' . (int) $term->term_id . '" ' . checked($checked, true, false) . '>';
    echo '<span>' . esc_html($term->name) . '</span>';
    echo '</label>';

    $children = get_terms([
      'taxonomy'   => 'product_cat',
      'hide_empty' => false,
      'orderby'    => 'name',
      'order'      => 'ASC',
      'parent'     => (int) $term->term_id,
    ]);

    if (!is_wp_error($children) && !empty($children)) {
      foreach ($children as $child) {
        self::render_scope_cat_row($child, $selected_ids, $field_name, $level + 1);
      }
    }

    echo '</div>';
  }
}