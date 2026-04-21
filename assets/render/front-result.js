(function ($) {
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
      dbg('front-result parseView JSON error', e && e.message ? e.message : e);
    }
    return view || {};
  }

  function getResultsRootByGroup(group) {
    if (!group) return $();
    var $root = $('.gwsfb[data-group="' + group + '"][data-mode="results"]').first();
    if ($root.length) return $root;
    return $('.gwsfb[data-group="' + group + '"][data-mode="both"]').first();
  }

  function getFilterRootByGroup(group) {
    if (!group) return $();
    var $root = $('.gwsfb[data-group="' + group + '"][data-mode="filters"]').first();
    if ($root.length) return $root;
    $root = $('.gwsfb[data-group="' + group + '"][data-mode="both"]').first();
    if ($root.length) return $root;
    return $('.gwsfb[data-group="' + group + '"]').first();
  }

  function showResults($root) {
    if (!$root || !$root.length) return;
    $root.find('.gwsfb__results').css('opacity', 1);
  }

  function setLoading($root, on) {
    if (!$root || !$root.length) return;
    $root.toggleClass('gwsfb-is-loading', !!on);
  }

  function getLoadCount($root) {
    if (!$root || !$root.length) return 0;
    var n = parseInt($root.attr('data-gwsfb-loadcount') || '0', 10);
    return isNaN(n) || n < 0 ? 0 : n;
  }

  function setLoadCount($root, count) {
    if (!$root || !$root.length) return;
    count = parseInt(count, 10);
    if (isNaN(count) || count < 0) count = 0;
    $root.attr('data-gwsfb-loadcount', String(count));
    setLoading($root, count > 0);
  }

  function beginLoading($root) {
    if (!$root || !$root.length) return;
    setLoadCount($root, getLoadCount($root) + 1);
  }

  function endLoading($root) {
    if (!$root || !$root.length) return;
    var next = getLoadCount($root) - 1;
    if (next < 0) next = 0;
    setLoadCount($root, next);
  }

  function beginLoadingGroup(group, $resultsRootOverride) {
    if (!group) return;

    var $r = ($resultsRootOverride && $resultsRootOverride.length) ? $resultsRootOverride : getResultsRootByGroup(group);
    var $f = getFilterRootByGroup(group);

    var rid = $r.length ? ($r.attr('id') || '') : '';
    var fid = $f.length ? ($f.attr('id') || '') : '';

    if ($r.length) beginLoading($r);
    if ($f.length) {
      if (!(rid && fid && rid === fid)) beginLoading($f);
    }
  }

  function endLoadingGroup(group, $resultsRootOverride) {
    if (!group) return;

    var $r = ($resultsRootOverride && $resultsRootOverride.length) ? $resultsRootOverride : getResultsRootByGroup(group);
    var $f = getFilterRootByGroup(group);

    var rid = $r.length ? ($r.attr('id') || '') : '';
    var fid = $f.length ? ($f.attr('id') || '') : '';

    if ($r.length) endLoading($r);
    if ($f.length) {
      if (!(rid && fid && rid === fid)) endLoading($f);
    }
  }

  function hydrateProductImages($scope) {
    if (!$scope || !$scope.length) return;

    $scope.find('.gwsr-img').each(function () {
      var $wrap = $(this);
      var $img = $wrap.find('img.gwsr-product-img').first();

      $wrap.removeClass('is-loaded is-error gwsr-img--ready');

      if (!$img.length) return;

      function markLoaded() {
        $wrap.addClass('gwsr-img--ready is-loaded').removeClass('is-error');
      }

      function markError() {
        $wrap.addClass('is-error').removeClass('is-loaded gwsr-img--ready');
      }

      $img.off('.gwsfb_img_state');

      if ($img[0] && $img[0].complete) {
        if ($img[0].naturalWidth > 0) markLoaded();
        else markError();
      } else {
        $img
          .on('load.gwsfb_img_state', function () { markLoaded(); })
          .on('error.gwsfb_img_state', function () { markError(); });
      }
    });
  }

  function waitForImages($scope, timeoutMs) {
    timeoutMs = timeoutMs || 12000;

    return new Promise(function (resolve) {
      if (!$scope || !$scope.length) { resolve(); return; }

      var $imgs = $scope.find('img');
      if (!$imgs.length) { resolve(); return; }

      var done = false;
      var remaining = 0;

      function finish() {
        if (done) return;
        done = true;
        resolve();
      }

      var timer = setTimeout(function () { finish(); }, timeoutMs);

      $imgs.each(function () {
        var img = this;
        if (img && img.complete && img.naturalWidth !== 0) return;

        remaining++;

        $(img).one('load.gwsfb_img error.gwsfb_img', function () {
          remaining--;
          if (remaining <= 0) {
            clearTimeout(timer);
            finish();
          }
        });

        setTimeout(function () {
          if (img && img.complete) {
            $(img).triggerHandler('load.gwsfb_img');
          }
        }, 0);
      });

      if (remaining <= 0) {
        clearTimeout(timer);
        finish();
      }
    });
  }

  function getOrderbyFromUI(group, $root) {
    var orderby = '';
    if (NS.collectSortReq) {
      var s = NS.collectSortReq(group) || {};
      if (s.orderby !== undefined) orderby = String(s.orderby || '');
    }
    if (!orderby) {
      var view = parseView($root);
      if (view && view.sort_default) orderby = String(view.sort_default);
    }
    if (!orderby) orderby = 'menu_order';
    return orderby;
  }

  function syncResponsiveView(group, $root) {
    if (NS.syncResponsiveViewForGroup && group) {
      return NS.syncResponsiveViewForGroup(group);
    }
    return false;
  }

  function initInitialReq() {
    var $roots = $('.gwsfb[data-mode="results"], .gwsfb[data-mode="both"]');
    $roots.each(function () {
      var $root = $(this);
      var group = $root.attr('data-group') || '';
      if (!group) return;

      syncResponsiveView(group, $root);

      if (!NS._lastReqByGroup[group]) {
        NS._lastReqByGroup[group] = { page: 1, orderby: getOrderbyFromUI(group, $root) };
        dbg('front-result initInitialReq', group, NS._lastReqByGroup[group]);
      }
    });
  }

  function buildReqFromUI(group, page) {
    var req = { page: page || 1, orderby: '' };

    if (NS.collectFilterReq) {
      var f = NS.collectFilterReq(group) || {};
      if (f && typeof f === 'object') {
        Object.keys(f).forEach(function (key) {
          if (key === 'page') return;
          req[key] = f[key];
        });
      }
    }

    if (NS.collectSortReq) {
      var s = NS.collectSortReq(group) || {};
      if (s.orderby !== undefined) req.orderby = String(s.orderby || '');
    }

    if (!req.orderby) req.orderby = 'menu_order';

    return req;
  }

  function ajaxRenderByGroup(group, page, $rootOverride, options) {
    if (!group) return;

    options = options || {};
    var reuseLast = !!options.reuseLast;

    var $root = ($rootOverride && $rootOverride.length) ? $rootOverride : $();
    if (!$root.length || !$root.is('.gwsfb') || !($root.attr('data-set-id') || '')) {
      $root = getResultsRootByGroup(group);
    }
    if (!$root.length) {
      dbg('front-result ajaxRenderByGroup root not found', group);
      return;
    }

    syncResponsiveView(group, $root);

    var setId = $root.attr('data-set-id') || '';
    if (!setId) {
      dbg('front-result ajaxRenderByGroup missing setId', group);
      return;
    }

    var view = parseView($root);
    var last = NS._lastReqByGroup[group] || null;
    var req;

    function ensureLastBase() {
      var base = { page: 1, orderby: getOrderbyFromUI(group, $root) };
      NS._lastReqByGroup[group] = $.extend(true, {}, base);
      return NS._lastReqByGroup[group];
    }

    if (reuseLast) {
      if (!last) last = ensureLastBase();
      req = $.extend(true, {}, last);
      req.page = page || req.page || 1;

      if (options.refreshSort && NS.collectSortReq) {
        var s = NS.collectSortReq(group) || {};
        if (s.orderby !== undefined) req.orderby = String(s.orderby || '');
        req.page = 1;
        NS._lastReqByGroup[group] = $.extend(true, {}, req);
      }
    } else {
      req = buildReqFromUI(group, page || 1);
      NS._lastReqByGroup[group] = $.extend(true, {}, req);
    }

    delete req.per_page;
    delete req.posts_per_page;

    var ajaxUrl = '';
    if (window.GWSFB && GWSFB.ajaxurl) ajaxUrl = GWSFB.ajaxurl;
    else if (typeof window.ajaxurl === 'string') ajaxUrl = window.ajaxurl;
    if (!ajaxUrl) {
      dbg('front-result ajaxRenderByGroup missing ajaxUrl');
      return;
    }

    dbg('front-result ajax request', { group: group, setId: setId, req: req, view: view, reuseLast: reuseLast });

    beginLoadingGroup(group, $root);

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'gwsfb_filter',
        nonce: (window.GWSFB && GWSFB.nonce) ? GWSFB.nonce : '',
        set_id: setId,
        group: group,
        req: req,
        view: view,
        calc_terms: reuseLast ? 0 : 1
      }
    }).done(function (res) {
      dbg('front-result ajax done', { group: group, success: !!(res && res.success), response: res });

      if (res && res.success && res.data && typeof res.data.html === 'string') {
        $root.find('.gwsfb__results').html(res.data.html);
      } else {
        dbg('front-result ajax invalid response shape', res);
      }

      try { $root.attr('data-view', JSON.stringify(view)); } catch (e) {
        dbg('front-result data-view stringify error', e && e.message ? e.message : e);
      }
      if (view && view.layout) $root.attr('data-layout', view.layout);

      var $scope = $root.find('.gwsfb__results').first();
      hydrateProductImages($scope);

      if (res && res.success && res.data && res.data.available_terms) {
        $(document).trigger(NS.events.AVAILABLE, [{
          group: group,
          available_terms: res.data.available_terms
        }]);
      }

      if (NS.applyAttrVisibilityMode) NS.applyAttrVisibilityMode(group);

      $(document).trigger(NS.events.AFTER_UPDATE, [{
        group: group,
        response: res || null
      }]);
    }).fail(function (xhr, status, err) {
      dbg('front-result ajax fail', {
        group: group,
        status: status,
        error: err,
        responseText: xhr && xhr.responseText ? xhr.responseText : ''
      });
    }).always(function () {
      showResults($root);

      var $scope = $root.find('.gwsfb__results').first();
      hydrateProductImages($scope);

      waitForImages($scope, 12000).then(function () {
        endLoadingGroup(group, $root);
        showResults($root);
        hydrateProductImages($scope);
      });
    });
  }

  NS.ajaxRenderByGroup = ajaxRenderByGroup;

  function initInitialLoading() {
    var $roots = $('.gwsfb[data-mode="results"], .gwsfb[data-mode="both"]');
    $roots.each(function () {
      var $root = $(this);
      var group = $root.attr('data-group') || '';
      if (!group) return;

      beginLoadingGroup(group, $root);
      showResults($root);

      var $scope = $root.find('.gwsfb__results').first();
      hydrateProductImages($scope);

      waitForImages($scope, 12000).then(function () {
        endLoadingGroup(group, $root);
        showResults($root);
        hydrateProductImages($scope);
      });
    });
  }

  function bootInitialResponsiveRender() {
    if (NS._didInitialResponsiveRender) return;
    NS._didInitialResponsiveRender = true;

    var doneGroups = {};

    $('.gwsfb[data-mode="results"], .gwsfb[data-mode="both"]').each(function () {
      var $root = $(this);
      var group = String($root.attr('data-group') || '');
      if (!group) return;
      if (doneGroups[group]) return;

      doneGroups[group] = true;

      syncResponsiveView(group, $root);

      dbg('front-result initial responsive render', { group: group });

      ajaxRenderByGroup(group, 1, $root, { reuseLast: false });
    });
  }

  function bindPagination() {
    $(document)
      .off('click.gwsfb_pager', '.gwsfb__page')
      .on('click.gwsfb_pager', '.gwsfb__page', function (e) {
        e.preventDefault();

        var $any = $(this).closest('.gwsfb');
        var group = $any.attr('data-group') || '';
        if (!group) return;

        var page = parseInt($(this).attr('data-page') || '1', 10);
        if (!page || page < 1) page = 1;

        dbg('front-result pagination click', { group: group, page: page });
        ajaxRenderByGroup(group, page, null, { reuseLast: true });
      });
  }

  function bindOptionsToggle() {
    $(document)
      .off('click.gwsfb_opt', '.gwsr-toggle')
      .on('click.gwsfb_opt', '.gwsr-toggle', function () {
        var $btn = $(this);
        var $card = $btn.closest('.gwsr-card');
        var $panel = $card.find('.gwsr-options').first();
        var isOpen = !$panel.attr('hidden');

        if (isOpen) {
          $panel.attr('hidden', 'hidden');
          $btn.attr('aria-expanded', 'false');
        } else {
          $panel.removeAttr('hidden');
          $btn.attr('aria-expanded', 'true');
        }
      });
  }

  function bindEventBridge() {
    $(document)
      .off(NS.events.APPLY + '.gwsfb_result')
      .on(NS.events.APPLY + '.gwsfb_result', function (e, payload) {
        if (!payload || !payload.group) return;
        dbg('front-result APPLY event', payload);
        ajaxRenderByGroup(payload.group, payload.page || 1, null, { reuseLast: false });
      });

    $(document)
      .off(NS.events.RESET + '.gwsfb_result')
      .on(NS.events.RESET + '.gwsfb_result', function (e, payload) {
        if (!payload || !payload.group) return;
        dbg('front-result RESET event', payload);

        if (NS.resetSortUI) NS.resetSortUI(payload.group, { silent: true });
        if (NS.initPriceSliders) NS.initPriceSliders();

        ajaxRenderByGroup(payload.group, 1, null, { reuseLast: true, refreshSort: true });
      });

    $(document)
      .off(NS.events.SORT_CHANGE + '.gwsfb_result')
      .on(NS.events.SORT_CHANGE + '.gwsfb_result', function (e, payload) {
        if (!payload || !payload.group) return;
        dbg('front-result SORT_CHANGE event', payload);
        ajaxRenderByGroup(payload.group, 1, null, { reuseLast: true, refreshSort: true });
      });
  }

  function bindResponsiveResize() {
    var timer = null;
    var lastDevice = (NS.getResponsiveDevice && typeof NS.getResponsiveDevice === 'function')
      ? NS.getResponsiveDevice()
      : '';

    $(window)
      .off('resize.gwsfb_results_responsive')
      .on('resize.gwsfb_results_responsive', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          var device = (NS.getResponsiveDevice && typeof NS.getResponsiveDevice === 'function')
            ? NS.getResponsiveDevice()
            : '';

          if (device === lastDevice) return;
          lastDevice = device;

          $('.gwsfb[data-mode="results"], .gwsfb[data-mode="both"]').each(function () {
            var $root = $(this);
            var group = String($root.attr('data-group') || '');
            if (!group) return;

            var changed = syncResponsiveView(group, $root);
            if (!changed) return;

            var last = NS._lastReqByGroup[group] || null;
            var page = last && last.page ? parseInt(last.page, 10) || 1 : 1;

            dbg('front-result responsive resize rerender', { group: group, device: device, page: page });
            ajaxRenderByGroup(group, page, $root, { reuseLast: true });
          });
        }, 120);
      });
  }

  $(function () {
    initInitialReq();
    initInitialLoading();
    bindPagination();
    bindOptionsToggle();
    bindEventBridge();
    bindResponsiveResize();
    bootInitialResponsiveRender();
  });
})(jQuery);