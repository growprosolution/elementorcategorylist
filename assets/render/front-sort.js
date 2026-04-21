(function ($) {
  'use strict';

  window.GWSFB_FE = window.GWSFB_FE || {};
  var NS = window.GWSFB_FE;

  NS.events = NS.events || {
    APPLY: 'gwsfb:apply',
    RESET: 'gwsfb:reset',
    SORT_CHANGE: 'gwsfb:sort_change',
    AVAILABLE: 'gwsfb:available_terms',
    AFTER_UPDATE: 'gwsfb:after_update'
  };

  NS._lastReqByGroup = NS._lastReqByGroup || {};

  function dbg() {
    if (window.GWSFB_DEBUG && typeof window.GWSFB_DEBUG.log === 'function') {
      try { window.GWSFB_DEBUG.log.apply(window.GWSFB_DEBUG, arguments); } catch (e) {}
    }
  }

  function parseView($root) {
    var view = {};
    try {
      var raw = $root.attr('data-view');
      if (raw) view = JSON.parse(raw);
    } catch (e) {
      dbg('front-sort parseView JSON error', e && e.message ? e.message : e);
    }
    return view || {};
  }

  function writeView($root, view) {
    try { $root.attr('data-view', JSON.stringify(view || {})); }
    catch (e) { dbg('front-sort writeView JSON error', e && e.message ? e.message : e); }
  }

  function getRootByGroup(group) {
    if (!group) return $();
    var $root = $('.gwsfb[data-group="' + group + '"][data-mode="results"]').first();
    if ($root.length) return $root;
    $root = $('.gwsfb[data-group="' + group + '"][data-mode="both"]').first();
    if ($root.length) return $root;
    return $('.gwsfb[data-group="' + group + '"]').first();
  }

  function getGroupFromElem($el) {
    var $root = $el.closest('.gwsfb');
    return $root.data('group') || '';
  }

  function getResponsiveDevice() {
    var w = window.innerWidth || document.documentElement.clientWidth || 9999;
    if (w <= 767) return 'mobile';
    if (w <= 1024) return 'tablet';
    return 'desktop';
  }

  function toInt(val, fallback) {
    var n = parseInt(val, 10);
    return isNaN(n) ? fallback : n;
  }

  function toFloat(val, fallback) {
    var n = parseFloat(val);
    return isNaN(n) ? fallback : n;
  }

  function clamp(num, min, max) {
    num = Number(num);
    if (isNaN(num)) return min;
    if (num < min) return min;
    if (num > max) return max;
    return num;
  }

  function sanitizeChoice(val, allowed, fallback) {
    val = String(val || '').toLowerCase();
    return allowed.indexOf(val) !== -1 ? val : fallback;
  }

  function boolVal(val, fallback) {
    if (val === undefined || val === null || val === '') return fallback ? 1 : 0;
    return val ? 1 : 0;
  }

  function getActiveLayout(view) {
    var layouts = (view && $.isArray(view.layouts)) ? view.layouts : [];
    var activeId = view && view.layout ? String(view.layout) : '';

    if (!layouts.length) return null;

    var i;
    for (i = 0; i < layouts.length; i++) {
      if (layouts[i] && String(layouts[i].id || '') === activeId) {
        return layouts[i];
      }
    }

    return layouts[0] || null;
  }

  function pickChoice(layout, base, device, fallback, allowed) {
    var key = base + '_' + device;
    if (layout && layout[key] !== undefined && layout[key] !== null && layout[key] !== '') {
      return sanitizeChoice(layout[key], allowed, fallback);
    }
    if (layout && layout[base] !== undefined && layout[base] !== null && layout[base] !== '') {
      return sanitizeChoice(layout[base], allowed, fallback);
    }
    return fallback;
  }

  function pickNumber(layout, base, device, fallback, min, max) {
    var key = base + '_' + device;
    var val = null;

    if (layout && layout[key] !== undefined && layout[key] !== null && layout[key] !== '') {
      val = layout[key];
    } else if (layout && layout[base] !== undefined && layout[base] !== null && layout[base] !== '') {
      val = layout[base];
    } else {
      val = fallback;
    }

    return clamp(parseFloat(val), min, max);
  }

  function pickBool(layout, base, device, fallback) {
    var key = base + '_' + device;
    if (layout && layout[key] !== undefined) return boolVal(layout[key], fallback);
    if (layout && layout[base] !== undefined) return boolVal(layout[base], fallback);
    return fallback ? 1 : 0;
  }

  function applyResponsiveLayoutState(view) {
    view = view || {};

    var active = getActiveLayout(view);
    if (!active) return view;

    var device = getResponsiveDevice();
    var type = String(active.type || 'grid') === 'list' ? 'list' : 'grid';

    var colsDesktop = Math.max(1, Math.min(6, toInt(active.columns_desktop, toInt(active.columns, 4))));
    var colsTablet = Math.max(1, Math.min(6, toInt(active.columns_tablet, colsDesktop)));
    var colsMobile = Math.max(1, Math.min(6, toInt(active.columns_mobile, colsTablet)));

    if (type === 'list') {
      colsDesktop = 1;
      colsTablet = 1;
      colsMobile = 1;
    }

    var rowsDesktop = Math.max(0, toInt(active.rows_desktop, toInt(active.rows, 0)));
    var rowsTablet = Math.max(0, toInt(active.rows_tablet, rowsDesktop));
    var rowsMobile = Math.max(0, toInt(active.rows_mobile, rowsTablet));

    var resolvedCols = colsDesktop;
    var resolvedRows = rowsDesktop;

    if (device === 'tablet') {
      resolvedCols = colsTablet;
      resolvedRows = rowsTablet;
    } else if (device === 'mobile') {
      resolvedCols = colsMobile;
      resolvedRows = rowsMobile;
    }

    if (type === 'list') {
      resolvedCols = 1;
    }

    var listMobileLayoutDesktop = pickChoice(active, 'list_mobile_layout', 'desktop', 'row', ['row', 'column']);
    var listMobileLayoutTablet = pickChoice(active, 'list_mobile_layout', 'tablet', listMobileLayoutDesktop, ['row', 'column']);
    var listMobileLayoutMobile = pickChoice(active, 'list_mobile_layout', 'mobile', listMobileLayoutTablet, ['row', 'column']);
    var resolvedListMobileLayout = pickChoice(active, 'list_mobile_layout', device, 'row', ['row', 'column']);

    var listMobileImageWidthDesktop = pickNumber(active, 'list_mobile_image_width', 'desktop', 28, 15, 80);
    var listMobileImageWidthTablet = pickNumber(active, 'list_mobile_image_width', 'tablet', listMobileImageWidthDesktop, 15, 80);
    var listMobileImageWidthMobile = pickNumber(active, 'list_mobile_image_width', 'mobile', listMobileImageWidthTablet, 15, 80);
    var resolvedListMobileImageWidth = pickNumber(active, 'list_mobile_image_width', device, 28, 15, 80);

    var listMobileVerticalAlignDesktop = pickChoice(active, 'list_mobile_vertical_align', 'desktop', 'flex-start', ['flex-start', 'center', 'flex-end']);
    var listMobileVerticalAlignTablet = pickChoice(active, 'list_mobile_vertical_align', 'tablet', listMobileVerticalAlignDesktop, ['flex-start', 'center', 'flex-end']);
    var listMobileVerticalAlignMobile = pickChoice(active, 'list_mobile_vertical_align', 'mobile', listMobileVerticalAlignTablet, ['flex-start', 'center', 'flex-end']);
    var resolvedListMobileVerticalAlign = pickChoice(active, 'list_mobile_vertical_align', device, 'flex-start', ['flex-start', 'center', 'flex-end']);

    var titleAlignDesktop = pickChoice(active, 'title_align', 'desktop', 'left', ['left', 'center', 'right']);
    var titleAlignTablet = pickChoice(active, 'title_align', 'tablet', titleAlignDesktop, ['left', 'center', 'right']);
    var titleAlignMobile = pickChoice(active, 'title_align', 'mobile', titleAlignTablet, ['left', 'center', 'right']);
    var resolvedTitleAlign = pickChoice(active, 'title_align', device, 'left', ['left', 'center', 'right']);

    var descAlignDesktop = pickChoice(active, 'desc_align', 'desktop', 'left', ['left', 'center', 'right']);
    var descAlignTablet = pickChoice(active, 'desc_align', 'tablet', descAlignDesktop, ['left', 'center', 'right']);
    var descAlignMobile = pickChoice(active, 'desc_align', 'mobile', descAlignTablet, ['left', 'center', 'right']);
    var resolvedDescAlign = pickChoice(active, 'desc_align', device, 'left', ['left', 'center', 'right']);

    var priceAlignDesktop = pickChoice(active, 'price_align', 'desktop', 'left', ['left', 'center', 'right']);
    var priceAlignTablet = pickChoice(active, 'price_align', 'tablet', priceAlignDesktop, ['left', 'center', 'right']);
    var priceAlignMobile = pickChoice(active, 'price_align', 'mobile', priceAlignTablet, ['left', 'center', 'right']);
    var resolvedPriceAlign = pickChoice(active, 'price_align', device, 'left', ['left', 'center', 'right']);

    var moreAlignDesktop = pickChoice(active, 'more_align', 'desktop', 'left', ['left', 'center', 'right']);
    var moreAlignTablet = pickChoice(active, 'more_align', 'tablet', moreAlignDesktop, ['left', 'center', 'right']);
    var moreAlignMobile = pickChoice(active, 'more_align', 'mobile', moreAlignTablet, ['left', 'center', 'right']);
    var resolvedMoreAlign = pickChoice(active, 'more_align', device, 'left', ['left', 'center', 'right']);

    var cardJustifyDesktop = pickChoice(active, 'card_justify_content', 'desktop', 'flex-start', ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly']);
    var cardJustifyTablet = pickChoice(active, 'card_justify_content', 'tablet', cardJustifyDesktop, ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly']);
    var cardJustifyMobile = pickChoice(active, 'card_justify_content', 'mobile', cardJustifyTablet, ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly']);
    var resolvedCardJustify = pickChoice(active, 'card_justify_content', device, 'flex-start', ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly']);

    var showTitleDesktop = pickBool(active, 'show_title', 'desktop', 1);
    var showTitleTablet = pickBool(active, 'show_title', 'tablet', showTitleDesktop);
    var showTitleMobile = pickBool(active, 'show_title', 'mobile', showTitleTablet);
    var resolvedShowTitle = pickBool(active, 'show_title', device, 1);

    var showPriceDesktop = pickBool(active, 'show_price', 'desktop', 1);
    var showPriceTablet = pickBool(active, 'show_price', 'tablet', showPriceDesktop);
    var showPriceMobile = pickBool(active, 'show_price', 'mobile', showPriceTablet);
    var resolvedShowPrice = pickBool(active, 'show_price', device, 1);

    var showRatingDesktop = pickBool(active, 'show_rating', 'desktop', 1);
    var showRatingTablet = pickBool(active, 'show_rating', 'tablet', showRatingDesktop);
    var showRatingMobile = pickBool(active, 'show_rating', 'mobile', showRatingTablet);
    var resolvedShowRating = pickBool(active, 'show_rating', device, 1);

    var showAddToCartDesktop = pickBool(active, 'show_add_to_cart', 'desktop', 1);
    var showAddToCartTablet = pickBool(active, 'show_add_to_cart', 'tablet', showAddToCartDesktop);
    var showAddToCartMobile = pickBool(active, 'show_add_to_cart', 'mobile', showAddToCartTablet);
    var resolvedShowAddToCart = pickBool(active, 'show_add_to_cart', device, 1);

    var showDescriptionDesktop = pickBool(active, 'show_description', 'desktop', 0);
    var showDescriptionTablet = pickBool(active, 'show_description', 'tablet', showDescriptionDesktop);
    var showDescriptionMobile = pickBool(active, 'show_description', 'mobile', showDescriptionTablet);
    var resolvedShowDescription = pickBool(active, 'show_description', device, 0);

    var showViewMoreDesktop = pickBool(active, 'show_view_more', 'desktop', 1);
    var showViewMoreTablet = pickBool(active, 'show_view_more', 'tablet', showViewMoreDesktop);
    var showViewMoreMobile = pickBool(active, 'show_view_more', 'mobile', showViewMoreTablet);
    var resolvedShowViewMore = pickBool(active, 'show_view_more', device, 1);

    view.layout = String(active.id || view.layout || '');
    view.layout_id = String(active.id || view.layout_id || '');
    view.layout_type = type;
    view.responsive_device = device;

    view.columns_desktop = colsDesktop;
    view.columns_tablet = colsTablet;
    view.columns_mobile = colsMobile;

    view.rows_desktop = rowsDesktop;
    view.rows_tablet = rowsTablet;
    view.rows_mobile = rowsMobile;

    view.columns = resolvedCols;
    view.rows = resolvedRows;

    view.show_title = resolvedShowTitle;
    view.show_title_desktop = showTitleDesktop;
    view.show_title_tablet = showTitleTablet;
    view.show_title_mobile = showTitleMobile;

    view.show_price = resolvedShowPrice;
    view.show_price_desktop = showPriceDesktop;
    view.show_price_tablet = showPriceTablet;
    view.show_price_mobile = showPriceMobile;

    view.show_rating = resolvedShowRating;
    view.show_rating_desktop = showRatingDesktop;
    view.show_rating_tablet = showRatingTablet;
    view.show_rating_mobile = showRatingMobile;

    view.show_add_to_cart = resolvedShowAddToCart;
    view.show_add_to_cart_desktop = showAddToCartDesktop;
    view.show_add_to_cart_tablet = showAddToCartTablet;
    view.show_add_to_cart_mobile = showAddToCartMobile;

    view.show_description = resolvedShowDescription;
    view.show_description_desktop = showDescriptionDesktop;
    view.show_description_tablet = showDescriptionTablet;
    view.show_description_mobile = showDescriptionMobile;

    view.show_view_more = resolvedShowViewMore;
    view.show_view_more_desktop = showViewMoreDesktop;
    view.show_view_more_tablet = showViewMoreTablet;
    view.show_view_more_mobile = showViewMoreMobile;

    view.view_more_label = active.view_more_label || 'View more';

    view.small_options_enable = active.options_enable ? 1 : 0;
    view.small_options_open = active.options_open ? 1 : 0;
    view.options_label = active.options_label || 'Options';

    view.list_mobile_layout = resolvedListMobileLayout;
    view.list_mobile_layout_desktop = listMobileLayoutDesktop;
    view.list_mobile_layout_tablet = listMobileLayoutTablet;
    view.list_mobile_layout_mobile = listMobileLayoutMobile;

    view.list_mobile_image_width = resolvedListMobileImageWidth;
    view.list_mobile_image_width_desktop = listMobileImageWidthDesktop;
    view.list_mobile_image_width_tablet = listMobileImageWidthTablet;
    view.list_mobile_image_width_mobile = listMobileImageWidthMobile;

    view.list_mobile_vertical_align = resolvedListMobileVerticalAlign;
    view.list_mobile_vertical_align_desktop = listMobileVerticalAlignDesktop;
    view.list_mobile_vertical_align_tablet = listMobileVerticalAlignTablet;
    view.list_mobile_vertical_align_mobile = listMobileVerticalAlignMobile;

    view.title_align = resolvedTitleAlign;
    view.title_align_desktop = titleAlignDesktop;
    view.title_align_tablet = titleAlignTablet;
    view.title_align_mobile = titleAlignMobile;

    view.desc_align = resolvedDescAlign;
    view.desc_align_desktop = descAlignDesktop;
    view.desc_align_tablet = descAlignTablet;
    view.desc_align_mobile = descAlignMobile;

    view.price_align = resolvedPriceAlign;
    view.price_align_desktop = priceAlignDesktop;
    view.price_align_tablet = priceAlignTablet;
    view.price_align_mobile = priceAlignMobile;

    view.more_align = resolvedMoreAlign;
    view.more_align_desktop = moreAlignDesktop;
    view.more_align_tablet = moreAlignTablet;
    view.more_align_mobile = moreAlignMobile;

    view.card_justify_content = resolvedCardJustify;
    view.card_justify_content_desktop = cardJustifyDesktop;
    view.card_justify_content_tablet = cardJustifyTablet;
    view.card_justify_content_mobile = cardJustifyMobile;

    view.layout_switcher_enable = ($.isArray(view.layouts) && view.layouts.length > 1) ? 1 : 0;

    var ppo = 0;
    if (resolvedRows > 0) {
      ppo = (type === 'list') ? resolvedRows : (resolvedRows * Math.max(1, resolvedCols));
    }
    view.per_page_override = ppo;

    return view;
  }

  NS.getResponsiveDevice = getResponsiveDevice;

  NS.syncResponsiveViewForGroup = function (group) {
    if (!group) return false;

    var $root = getRootByGroup(group);
    if (!$root.length) return false;

    var before = parseView($root);
    var beforeDevice = String(before.responsive_device || '');
    var beforeLayout = String(before.layout || '');
    var beforeCols = toInt(before.columns, 0);
    var beforeRows = toInt(before.rows, 0);
    var beforePpo = toInt(before.per_page_override, 0);

    var beforeListMobileLayout = String(before.list_mobile_layout || '');
    var beforeListMobileImageWidth = toFloat(before.list_mobile_image_width, 0);
    var beforeListMobileVerticalAlign = String(before.list_mobile_vertical_align || '');

    var beforeTitleAlign = String(before.title_align || '');
    var beforeDescAlign = String(before.desc_align || '');
    var beforePriceAlign = String(before.price_align || '');
    var beforeMoreAlign = String(before.more_align || '');
    var beforeCardJustify = String(before.card_justify_content || '');

    var beforeShowTitle = toInt(before.show_title, 0);
    var beforeShowPrice = toInt(before.show_price, 0);
    var beforeShowRating = toInt(before.show_rating, 0);
    var beforeShowAddToCart = toInt(before.show_add_to_cart, 0);
    var beforeShowDescription = toInt(before.show_description, 0);
    var beforeShowViewMore = toInt(before.show_view_more, 0);

    var view = applyResponsiveLayoutState(before);
    writeView($root, view);

    if (view.layout) {
      $root.attr('data-layout', view.layout);
    }

    return (
      beforeDevice !== String(view.responsive_device || '') ||
      beforeLayout !== String(view.layout || '') ||
      beforeCols !== toInt(view.columns, 0) ||
      beforeRows !== toInt(view.rows, 0) ||
      beforePpo !== toInt(view.per_page_override, 0) ||
      beforeListMobileLayout !== String(view.list_mobile_layout || '') ||
      beforeListMobileImageWidth !== toFloat(view.list_mobile_image_width, 0) ||
      beforeListMobileVerticalAlign !== String(view.list_mobile_vertical_align || '') ||
      beforeTitleAlign !== String(view.title_align || '') ||
      beforeDescAlign !== String(view.desc_align || '') ||
      beforePriceAlign !== String(view.price_align || '') ||
      beforeMoreAlign !== String(view.more_align || '') ||
      beforeCardJustify !== String(view.card_justify_content || '') ||
      beforeShowTitle !== toInt(view.show_title, 0) ||
      beforeShowPrice !== toInt(view.show_price, 0) ||
      beforeShowRating !== toInt(view.show_rating, 0) ||
      beforeShowAddToCart !== toInt(view.show_add_to_cart, 0) ||
      beforeShowDescription !== toInt(view.show_description, 0) ||
      beforeShowViewMore !== toInt(view.show_view_more, 0)
    );
  };

  function buildInitialReq($root) {
    var group = $root.data('group') || '';
    var setId = $root.data('set-id') || '';
    var view = parseView($root);

    var $sort = $root.find('.gwsfb__sortselect').first();
    var orderby = '';
    if ($sort.length) orderby = $sort.val() || '';
    if (!orderby && view.sort_default) orderby = view.sort_default;
    if (!orderby) orderby = 'menu_order';

    return {
      group: group,
      set_id: setId,
      page: 1,
      orderby: orderby
    };
  }

  NS.sendRequest = function (group, overrides) {
    if (!group) return;

    var $root = getRootByGroup(group);
    if (!$root.length) {
      dbg('front-sort sendRequest root not found', group);
      return;
    }

    NS.syncResponsiveViewForGroup(group);

    var view = parseView($root);

    var last = NS._lastReqByGroup[group] || buildInitialReq($root);
    var req = $.extend({}, last, overrides || {});

    delete req.per_page;
    delete req.posts_per_page;

    req.action = 'gwsfb_filter';
    req.nonce = (window.GWSFB && GWSFB.nonce) ? GWSFB.nonce : '';
    if (!req.group) req.group = group;
    if (!req.set_id) req.set_id = $root.data('set-id') || '';

    if (!req.orderby) {
      var s = NS.collectSortReq ? (NS.collectSortReq(group) || {}) : {};
      if (s && s.orderby !== undefined) req.orderby = String(s.orderby || '');
      if (!req.orderby && view.sort_default) req.orderby = String(view.sort_default);
      if (!req.orderby) req.orderby = 'menu_order';
    }

    NS._lastReqByGroup[group] = $.extend({}, req);

    dbg('front-sort sendRequest payload', { group: group, req: req, view: view });

    if (NS.ajaxRenderByGroup) {
      var p = (overrides && Object.prototype.hasOwnProperty.call(overrides, 'page')) ? (overrides.page || 1) : (req.page || 1);
      NS.ajaxRenderByGroup(group, p, $root, { reuseLast: true, refreshSort: false });
      return;
    }

    $root.addClass('gwsfb-is-loading');

    $.ajax({
      url: GWSFB.ajaxurl,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'gwsfb_filter',
        nonce: req.nonce,
        set_id: req.set_id,
        group: req.group,
        req: req,
        view: view
      }
    }).done(function (res) {
      dbg('front-sort fallback ajax done', res);
      var $results = $root.find('.gwsfb__results').first();
      if ($results.length && res && res.success && res.data && typeof res.data.html === 'string') {
        $results.html(res.data.html);
      }
      $(document).trigger(NS.events.AFTER_UPDATE, [{ group: group, response: res || null }]);
    }).fail(function (xhr, status, err) {
      dbg('front-sort fallback ajax fail', {
        status: status,
        error: err,
        responseText: xhr && xhr.responseText ? xhr.responseText : ''
      });
    }).always(function () {
      $root.removeClass('gwsfb-is-loading');
    });
  };

  NS.collectSortReq = function (group) {
    var $root = getRootByGroup(group);
    if (!$root.length) return { orderby: '' };

    var $sel = $root.find('.gwsfb__sortselect').first();
    var orderby = $sel.length ? String($sel.val() || '') : '';

    if (!orderby) {
      var view = parseView($root);
      if (view && view.sort_default) orderby = String(view.sort_default);
    }

    if (!orderby) orderby = 'menu_order';

    return { orderby: orderby };
  };

  NS.resetSortUI = function (group, options) {
    options = options || {};
    var silent = !!options.silent;

    var $root = getRootByGroup(group);
    if (!$root.length) return;

    NS.syncResponsiveViewForGroup(group);

    var view = parseView($root);
    var def = (view && view.sort_default) ? String(view.sort_default) : 'menu_order';

    var $sel = $root.find('.gwsfb__sortselect').first();
    if ($sel.length) {
      $sel.val(def);
      if (!silent) $sel.trigger('change');
    }

    var last = NS._lastReqByGroup[group] || buildInitialReq($root);
    last.orderby = def;
    last.page = 1;
    delete last.per_page;
    delete last.posts_per_page;
    NS._lastReqByGroup[group] = last;

    dbg('front-sort resetSortUI', { group: group, defaultOrderby: def, silent: silent });
  };

  function bindOptionsToggle() {
    $(document)
      .off('click.gwsfb_sort_opt_toggle', '.gwsr-toggle')
      .on('click.gwsfb_sort_opt_toggle', '.gwsr-toggle', function () {
        var $btn = $(this);
        var expanded = $btn.attr('aria-expanded') === 'true';
        var $panel = $btn.next('.gwsr-options');
        if (!$panel.length) return;

        expanded = !expanded;
        $btn.attr('aria-expanded', expanded ? 'true' : 'false');
        if (expanded) $panel.removeAttr('hidden');
        else $panel.attr('hidden', 'hidden');
      });
  }

  function bindLayoutSwitcher() {
    $(document)
      .off('click.gwsfb_layout_switch', '.gwsfb-results__layout-btn')
      .on('click.gwsfb_layout_switch', '.gwsfb-results__layout-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var group = getGroupFromElem($btn);
        if (!group) return;

        var layoutId = String($btn.data('layout') || '');
        if (!layoutId) return;

        var $root = getRootByGroup(group);
        if (!$root.length) return;

        var view = parseView($root);
        view.layout = layoutId;
        view = applyResponsiveLayoutState(view);

        writeView($root, view);
        $root.attr('data-layout', layoutId);

        var $allBtns = $btn.closest('.gwsfb-results__layout-toggle').find('.gwsfb-results__layout-btn');
        $allBtns.removeClass('is-active').attr('aria-pressed', 'false');
        $btn.addClass('is-active').attr('aria-pressed', 'true');

        var last = NS._lastReqByGroup[group] || buildInitialReq($root);
        if (NS.collectSortReq) {
          var s = NS.collectSortReq(group) || {};
          if (s.orderby !== undefined) last.orderby = String(s.orderby || '');
        }
        last.page = 1;
        delete last.per_page;
        delete last.posts_per_page;
        NS._lastReqByGroup[group] = last;

        dbg('front-sort layout click', { group: group, layoutId: layoutId, view: view, req: last });

        if (NS.ajaxRenderByGroup) NS.ajaxRenderByGroup(group, 1, $root, { reuseLast: true });
        else NS.sendRequest(group, { page: 1 });
      });
  }

  function bindSortSelect() {
    $(document)
      .off('change.gwsfb_sort_select', '.gwsfb__sortselect')
      .on('change.gwsfb_sort_select', '.gwsfb__sortselect', function () {
        var $sel = $(this);
        var group = getGroupFromElem($sel);
        if (!group) return;

        var orderby = String($sel.val() || 'menu_order');
        var $root = getRootByGroup(group);
        if (!$root.length) return;

        NS.syncResponsiveViewForGroup(group);

        var last = NS._lastReqByGroup[group] || buildInitialReq($root);
        last.orderby = orderby;
        last.page = 1;
        delete last.per_page;
        delete last.posts_per_page;
        NS._lastReqByGroup[group] = last;

        dbg('front-sort sort change', { group: group, orderby: orderby, req: last });

        $(document).trigger(NS.events.SORT_CHANGE, [{
          group: group,
          page: 1,
          orderby: orderby
        }]);
      });
  }

  function bindPagination() {
    $(document)
      .off('click.gwsfb_sort_pager_passthrough', '.gwsfb__page')
      .on('click.gwsfb_sort_pager_passthrough', '.gwsfb__page', function () {
        var $btn = $(this);
        var group = getGroupFromElem($btn);
        if (!group) return;

        var page = parseInt($btn.data('page'), 10) || 1;
        var $root = getRootByGroup(group);
        if (!$root.length) return;

        var last = NS._lastReqByGroup[group] || buildInitialReq($root);
        last.page = page;
        delete last.per_page;
        delete last.posts_per_page;
        NS._lastReqByGroup[group] = last;

        dbg('front-sort pagination state sync', { group: group, page: page, req: last });
      });
  }

  function bindFilterApply() {
    $(document)
      .off(NS.events.APPLY + '.gwsfb_sort')
      .on(NS.events.APPLY + '.gwsfb_sort', function (e, payload) {
        var group = '';
        if (typeof payload === 'string') { group = payload; payload = {}; }
        else if (payload && payload.group) group = payload.group;
        if (!group) return;

        var $root = getRootByGroup(group);
        if (!$root.length) return;

        NS.syncResponsiveViewForGroup(group);

        var baseReq = buildInitialReq($root);

        if (NS.collectFilterReq) {
          $.extend(baseReq, NS.collectFilterReq(group) || {});
        }

        if (payload && typeof payload === 'object') $.extend(baseReq, payload);

        if (NS.collectSortReq) {
          var s = NS.collectSortReq(group) || {};
          if (s.orderby !== undefined) baseReq.orderby = String(s.orderby || '');
        }

        baseReq.page = 1;
        delete baseReq.per_page;
        delete baseReq.posts_per_page;

        NS._lastReqByGroup[group] = $.extend({}, baseReq);

        dbg('front-sort APPLY sync base request', { group: group, req: baseReq });
      });
  }

  function bindFilterReset() {
    $(document)
      .off(NS.events.RESET + '.gwsfb_sort')
      .on(NS.events.RESET + '.gwsfb_sort', function (e, payload) {
        var group = '';
        if (typeof payload === 'string') { group = payload; payload = {}; }
        else if (payload && payload.group) group = payload.group;
        if (!group) return;

        var $root = getRootByGroup(group);
        if (!$root.length) return;

        NS.syncResponsiveViewForGroup(group);

        var baseReq = buildInitialReq($root);

        if (NS.collectSortReq) {
          var s = NS.collectSortReq(group) || {};
          if (s.orderby !== undefined) baseReq.orderby = String(s.orderby || '');
        }

        baseReq.page = 1;
        delete baseReq.per_page;
        delete baseReq.posts_per_page;

        NS._lastReqByGroup[group] = $.extend({}, baseReq);

        dbg('front-sort RESET sync base request', { group: group, req: baseReq });
      });
  }

  function primeInitialRequests() {
    $('.gwsfb[data-mode="results"], .gwsfb[data-mode="both"]').each(function () {
      var $root = $(this);
      var group = $root.data('group') || '';
      if (!group) return;

      NS.syncResponsiveViewForGroup(group);

      if (!NS._lastReqByGroup[group]) {
        NS._lastReqByGroup[group] = buildInitialReq($root);
        dbg('front-sort primeInitialRequests', group, NS._lastReqByGroup[group]);
      }
    });
  }

  $(function () {
    primeInitialRequests();
    bindOptionsToggle();
    bindLayoutSwitcher();
    bindSortSelect();
    bindPagination();
    bindFilterApply();
    bindFilterReset();
  });
})(jQuery);