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

  function getFilterRootByGroup(group) {
    if (!group) return $();
    var $root = $('.gwsfb[data-group="' + group + '"][data-mode="filters"]').first();
    if ($root.length) return $root;
    $root = $('.gwsfb[data-group="' + group + '"][data-mode="both"]').first();
    if ($root.length) return $root;
    return $('.gwsfb[data-group="' + group + '"]').first();
  }

  function getGroupFromElement($el) {
    var $root = $el.closest('.gwsfb');
    return String($root.attr('data-group') || '');
  }

  function setBlockState($block, open) {
    if (!$block || !$block.length) return;
    open = !!open;

    $block.toggleClass('is-open', open);
    $block.toggleClass('is-collapsed', !open);

    var $bar = $block.children('.gwsfb__titlebar').first();
    if ($bar.length) $bar.attr('aria-expanded', open ? 'true' : 'false');
  }

  function toggleBlock($block) {
    if (!$block || !$block.length) return;
    var isOpen = $block.hasClass('is-open') && !$block.hasClass('is-collapsed');
    setBlockState($block, !isOpen);
  }

  function getViewportMode() {
    var w = window.innerWidth || document.documentElement.clientWidth || 9999;
    if (w <= 767) return 'mobile';
    if (w <= 1024) return 'tablet';
    return 'desktop';
  }

  function getAttrVisibilityConfig(group) {
    var all = (window.GWSFB && window.GWSFB.attrVisibility) ? window.GWSFB.attrVisibility : {};
    var cfg = all && all[group] ? all[group] : {};
    return {
      desktop: cfg.desktop || 'show_all',
      tablet: cfg.tablet || cfg.desktop || 'show_all',
      mobile: cfg.mobile || cfg.tablet || cfg.desktop || 'show_all'
    };
  }

  NS.applyAttrVisibilityMode = function (group) {
    var $root = getFilterRootByGroup(group);
    if (!$root.length) return;

    var cfg = getAttrVisibilityConfig(group);
    var mode = cfg[getViewportMode()] || 'show_all';

    var $blocks = $root.find('.gwsfb__block[data-filter="attr"]').filter(function () {
      return $(this).css('display') !== 'none';
    });

    if (!$blocks.length) return;

    if (mode === 'hide_all') {
      $blocks.each(function () { setBlockState($(this), false); });
      return;
    }

    if (mode === 'hide_first') {
      $blocks.each(function (i) { setBlockState($(this), i === 0); });
      return;
    }

    $blocks.each(function () {
      if (!$(this).hasClass('is-open') && !$(this).hasClass('is-collapsed')) {
        setBlockState($(this), true);
      }
    });
  };

	function formatLabel(value, $slider) {
	  var n = parseFloat(value);
	  if (!isFinite(n)) n = 0;

	  var number;
	  if (Math.round(n) === n) number = String(Math.round(n));
	  else number = String(n.toFixed(2)).replace(/\.?0+$/, '');

	  var symbol = '$';
	  var pos = 'left';

	  if ($slider && $slider.length) {
		var s = String($slider.attr('data-currency-symbol') || '');
		var p = String($slider.attr('data-currency-pos') || '');
		if (s) symbol = s;
		if (p) pos = p;
	  }

	  switch (pos) {
		case 'right':
		  return number + symbol;
		case 'left_space':
		  return symbol + ' ' + number;
		case 'right_space':
		  return number + ' ' + symbol;
		case 'left':
		default:
		  return symbol + number;
	  }
	}

  function updateSinglePriceSlider($slider) {
    if (!$slider || !$slider.length) return;

    var $rangeWrap = $slider.find('.gwsfb-price__range').first();
    var $rangeMin = $slider.find('.gwsfb__price-range-min').first();
    var $rangeMax = $slider.find('.gwsfb__price-range-max').first();
    var $minInput = $slider.find('input[name="min_price"]').first();
    var $maxInput = $slider.find('input[name="max_price"]').first();

    if (!$rangeWrap.length || !$rangeMin.length || !$rangeMax.length || !$minInput.length || !$maxInput.length) return;

    var minBound = parseFloat($slider.attr('data-min'));
    var maxBound = parseFloat($slider.attr('data-max'));
    var minVal = parseFloat($rangeMin.val());
    var maxVal = parseFloat($rangeMax.val());

    if (!isFinite(minBound)) minBound = 0;
    if (!isFinite(maxBound)) maxBound = 0;
    if (!isFinite(minVal)) minVal = minBound;
    if (!isFinite(maxVal)) maxVal = maxBound;

    if (minVal < minBound) minVal = minBound;
    if (maxVal > maxBound) maxVal = maxBound;
    if (minVal > maxVal) minVal = maxVal;
    if (maxVal < minVal) maxVal = minVal;

    $rangeMin.val(minVal);
    $rangeMax.val(maxVal);

    $minInput.val(String(minVal));
    $maxInput.val(String(maxVal));

    var minPct = 0;
    var maxPct = 100;

    if (maxBound > minBound) {
      minPct = ((minVal - minBound) / (maxBound - minBound)) * 100;
      maxPct = ((maxVal - minBound) / (maxBound - minBound)) * 100;
    }

    $rangeWrap.css('--gwsfb-price-min-pct', minPct + '%');
    $rangeWrap.css('--gwsfb-price-max-pct', maxPct + '%');

	$slider.find('.gwsfb__price-label--min, .gwsfb-price__value--min').text(formatLabel(minBound, $slider));
	$slider.find('.gwsfb__price-label--current, .gwsfb-price__value--current').text(formatLabel(minVal, $slider) + ' - ' + formatLabel(maxVal, $slider));
	$slider.find('.gwsfb__price-label--max, .gwsfb-price__value--max').text(formatLabel(maxBound, $slider));
  }

  NS.initPriceSliders = function (scope) {
    var $scope = scope ? $(scope) : $(document);
    $scope.find('.gwsfb__price-slider, .gwsfb-price').each(function () {
      updateSinglePriceSlider($(this));
    });
  };

  function storeInitialState($root) {
    if (!$root || !$root.length) return;

    $root.find('input, select, textarea').each(function () {
      var $el = $(this);
      var type = String(($el.attr('type') || '')).toLowerCase();

      if (type === 'checkbox' || type === 'radio') {
        if ($el.attr('data-gwsfb-default-checked') === undefined) {
          $el.attr('data-gwsfb-default-checked', $el.prop('checked') ? '1' : '0');
        }
      } else {
        if ($el.attr('data-gwsfb-default-value') === undefined) {
          $el.attr('data-gwsfb-default-value', $el.val());
        }
      }
    });
  }

  function restoreInitialState($root) {
    if (!$root || !$root.length) return;

    $root.find('input, select, textarea').each(function () {
      var $el = $(this);
      var type = String(($el.attr('type') || '')).toLowerCase();

      if (type === 'checkbox' || type === 'radio') {
        $el.prop('checked', $el.attr('data-gwsfb-default-checked') === '1');
      } else {
        var def = $el.attr('data-gwsfb-default-value');
        if (def !== undefined) $el.val(def);
      }
    });

    NS.initPriceSliders($root);
  }

  function sameSet(a, b) {
    a = (a || []).slice().map(String).sort();
    b = (b || []).slice().map(String).sort();
    if (a.length !== b.length) return false;
    for (var i = 0; i < a.length; i++) if (a[i] !== b[i]) return false;
    return true;
  }

  function safeJsonParse(raw) {
    if (!raw) return {};
    try {
      return JSON.parse(raw);
    } catch (e) {
      return {};
    }
  }

  function normalizeAvailableTermsMap(raw) {
    var out = {};
    if (!raw || typeof raw !== 'object') return out;

    Object.keys(raw).forEach(function (tax) {
      var vals = raw[tax];
      var map = {};

      if (Array.isArray(vals)) {
        vals.forEach(function (v) {
          var key = String(parseInt(v, 10) || 0);
          if (key !== '0') map[key] = true;
        });
      }

      out[String(tax)] = map;
    });

    return out;
  }

  function setCheckboxOptionVisibility($input, show) {
    var $row = $input.closest('.gwsfb__opt');
    if (!$row.length) $row = $input.closest('label');

    if (show) {
      $row.show().removeClass('gwsfb-is-hidden-by-available');
      $input.prop('disabled', false);
    } else {
      $row.hide().addClass('gwsfb-is-hidden-by-available');
      $input.prop('disabled', true);
    }
  }

  function setSelectOptionVisibility($option, show) {
    $option.prop('hidden', !show);
    $option.prop('disabled', !show);
  }

  NS.applyAvailableTermsMap = function (group, rawMap) {
    var $root = getFilterRootByGroup(group);
    if (!$root.length) return;

    var map = normalizeAvailableTermsMap(rawMap);

    $root.find('.gwsfb__block[data-filter="attr"]').each(function () {
      var $block = $(this);
      var tax = String($block.attr('data-tax') || '');
      if (!tax) return;

      var available = map[tax] || {};
      var visibleCount = 0;
      var hasSelected = false;

      var $select = $block.find('select[name="attr[' + tax + '][]"]').first();

      if ($select.length) {
        $select.find('option').each(function () {
          var $opt = $(this);
          var val = String($opt.val() || '');

          if (!val) {
            setSelectOptionVisibility($opt, true);
            return;
          }

          var selected = $opt.prop('selected');
          if (selected) hasSelected = true;

          var show = selected || !!available[val];
          setSelectOptionVisibility($opt, show);

          if (show) visibleCount++;
        });
      } else {
        $block.find('input[type="checkbox"][name="attr[' + tax + '][]"]').each(function () {
          var $cb = $(this);
          var val = String($cb.val() || '');
          var checked = $cb.prop('checked');

          if (checked) hasSelected = true;

          var show = checked || !!available[val];
          setCheckboxOptionVisibility($cb, show);

          if (show) visibleCount++;
        });
      }

      var showBlock = (visibleCount > 0) || hasSelected;

      if (showBlock) {
        $block.show().removeClass('gwsfb-block-hidden-by-available');
      } else {
        $block.hide().addClass('gwsfb-block-hidden-by-available');
      }
    });

    NS.applyAttrVisibilityMode(group);
  };

  NS.applyInitialAvailableTerms = function (group) {
    var $root = getFilterRootByGroup(group);
    if (!$root.length) return;

    var raw = $root.attr('data-available-terms-initial');
    if (!raw) return;

    NS.applyAvailableTermsMap(group, safeJsonParse(raw));
  };

  NS.collectFilterReq = function (group) {
    var $root = getFilterRootByGroup(group);
    var req = { page: 1, cat: [], attr: {} };
    if (!$root.length) return req;

    $root.find('input[name="cat[]"]:checked').each(function () {
      var v = parseInt($(this).val(), 10);
      if (v > 0) req.cat.push(v);
    });

    $root.find('.gwsfb__block[data-filter="attr"]').each(function () {
      var $block = $(this);
      var tax = String($block.attr('data-tax') || '');
      if (!tax) return;

      var vals = [];

      $block.find('input[type="checkbox"][name="attr[' + tax + '][]"]:checked').each(function () {
        var v = parseInt($(this).val(), 10);
        if (v > 0) vals.push(v);
      });

      $block.find('select[name="attr[' + tax + '][]"]').each(function () {
        var raw = $(this).val();
        var v = parseInt(raw, 10);
        if (v > 0) vals.push(v);
      });

      vals = vals.filter(function (v, i, a) { return a.indexOf(v) === i; });
      if (vals.length) req.attr[tax] = vals;
    });

    (function () {
      var $block = $root.find('.gwsfb__block[data-filter="stock"]').first();
      if (!$block.length) return;

      var $cbs = $block.find('input[name="stock_statuses[]"]');
      if (!$cbs.length) return;

      var cur = [];
      var def = [];

      $cbs.each(function () {
        var $cb = $(this);
        var val = String($cb.val() || '');
        if (!val) return;

        if ($cb.prop('checked')) cur.push(val);

        var d = $cb.attr('data-gwsfb-default-checked');
        if (d === '1') def.push(val);
      });

      if (!sameSet(cur, def)) {
        req.stock_statuses = cur.slice();
      }
    })();

    $root.find('.gwsfb__price-slider, .gwsfb-price').each(function () {
      var $slider = $(this);
      var minDefault = parseFloat($slider.attr('data-min'));
      var maxDefault = parseFloat($slider.attr('data-max'));
      var minVal = parseFloat($slider.find('input[name="min_price"]').val());
      var maxVal = parseFloat($slider.find('input[name="max_price"]').val());

      if (!isFinite(minDefault)) minDefault = 0;
      if (!isFinite(maxDefault)) maxDefault = 0;
      if (!isFinite(minVal)) minVal = minDefault;
      if (!isFinite(maxVal)) maxVal = maxDefault;

      if (Math.abs(minVal - minDefault) > 0.00001 || Math.abs(maxVal - maxDefault) > 0.00001) {
        req.min_price = minVal;
        req.max_price = maxVal;
      }
    });

    if (!req.cat.length) delete req.cat;
    if (!Object.keys(req.attr).length) delete req.attr;

    return req;
  };

  function keepBarPositionStable(barEl, toggleFn) {
    if (!barEl || typeof toggleFn !== 'function') { toggleFn(); return; }

    var beforeTop = barEl.getBoundingClientRect().top;
    toggleFn();

    window.requestAnimationFrame(function () {
      var afterTop = barEl.getBoundingClientRect().top;
      var delta = afterTop - beforeTop;
      if (Math.abs(delta) > 1) window.scrollBy(0, delta);
    });
  }

  function bindTitlebarToggle() {
    $(document)
      .off('mousedown.gwsfb_filter_titlebar', '.gwsfb__titlebar')
      .on('mousedown.gwsfb_filter_titlebar', '.gwsfb__titlebar', function (e) { e.preventDefault(); });

    $(document)
      .off('click.gwsfb_filter_titlebar', '.gwsfb__titlebar')
      .on('click.gwsfb_filter_titlebar', '.gwsfb__titlebar', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $bar = $(this);
        var $block = $bar.closest('.gwsfb__block');
        if (!$block.length) return;

        keepBarPositionStable(this, function () { toggleBlock($block); });
      });

    $(document)
      .off('keydown.gwsfb_filter_titlebar', '.gwsfb__titlebar')
      .on('keydown.gwsfb_filter_titlebar', '.gwsfb__titlebar', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        e.stopPropagation();

        var $bar = $(this);
        var $block = $bar.closest('.gwsfb__block');
        if (!$block.length) return;

        keepBarPositionStable(this, function () { toggleBlock($block); });
      });
  }

  function bindPriceSlider() {
    $(document)
      .off('input.gwsfb_price_min change.gwsfb_price_min', '.gwsfb__price-range-min')
      .on('input.gwsfb_price_min change.gwsfb_price_min', '.gwsfb__price-range-min', function () {
        var $slider = $(this).closest('.gwsfb__price-slider, .gwsfb-price');
        if (!$slider.length) return;

        var $min = $slider.find('.gwsfb__price-range-min').first();
        var $max = $slider.find('.gwsfb__price-range-max').first();

        var minVal = parseFloat($min.val());
        var maxVal = parseFloat($max.val());

        if (!isFinite(minVal)) minVal = 0;
        if (!isFinite(maxVal)) maxVal = minVal;

        if (minVal > maxVal) $min.val(maxVal);

        updateSinglePriceSlider($slider);
      });

    $(document)
      .off('input.gwsfb_price_max change.gwsfb_price_max', '.gwsfb__price-range-max')
      .on('input.gwsfb_price_max change.gwsfb_price_max', '.gwsfb__price-range-max', function () {
        var $slider = $(this).closest('.gwsfb__price-slider, .gwsfb-price');
        if (!$slider.length) return;

        var $min = $slider.find('.gwsfb__price-range-min').first();
        var $max = $slider.find('.gwsfb__price-range-max').first();

        var minVal = parseFloat($min.val());
        var maxVal = parseFloat($max.val());

        if (!isFinite(minVal)) minVal = 0;
        if (!isFinite(maxVal)) maxVal = minVal;

        if (maxVal < minVal) $max.val(minVal);

        updateSinglePriceSlider($slider);
      });
  }

  function bindApplyReset() {
    $(document)
      .off('click.gwsfb_apply_btn', '.gwsfb__apply')
      .on('click.gwsfb_apply_btn', '.gwsfb__apply', function (e) {
        e.preventDefault();
        var group = getGroupFromElement($(this));
        if (!group) return;
        $(document).trigger(NS.events.APPLY, [{ group: group, page: 1 }]);
      });

    $(document)
      .off('click.gwsfb_reset_btn', '.gwsfb__reset')
      .on('click.gwsfb_reset_btn', '.gwsfb__reset', function (e) {
        e.preventDefault();
        var group = getGroupFromElement($(this));
        if (!group) return;

        var $root = getFilterRootByGroup(group);
        if ($root.length) {
          restoreInitialState($root);
          NS.applyInitialAvailableTerms(group);
          NS.applyAttrVisibilityMode(group);
        }

        $(document).trigger(NS.events.RESET, [{ group: group, page: 1 }]);
      });
  }

  function bindResizeVisibility() {
    var timer = null;
    $(window)
      .off('resize.gwsfb_attr_visibility')
      .on('resize.gwsfb_attr_visibility', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          $('.gwsfb[data-mode="filters"], .gwsfb[data-mode="both"]').each(function () {
            var group = String($(this).attr('data-group') || '');
            if (!group) return;
            NS.applyAttrVisibilityMode(group);
          });
        }, 80);
      });
  }

  function initRoots() {
    $('.gwsfb[data-mode="filters"], .gwsfb[data-mode="both"]').each(function () {
      var $root = $(this);
      var group = String($root.attr('data-group') || '');
      storeInitialState($root);
      NS.initPriceSliders($root);
      if (group) {
        NS.applyInitialAvailableTerms(group);
        NS.applyAttrVisibilityMode(group);
      }
    });
  }

  $(document)
    .off(NS.events.AVAILABLE + '.gwsfb_filter_available')
    .on(NS.events.AVAILABLE + '.gwsfb_filter_available', function (e, payload) {
      if (!payload || !payload.group) return;
      NS.applyAvailableTermsMap(payload.group, payload.available_terms || {});
    });

  $(document)
    .off(NS.events.AFTER_UPDATE + '.gwsfb_filter')
    .on(NS.events.AFTER_UPDATE + '.gwsfb_filter', function (e, payload) {
      if (!payload || !payload.group) return;
      var $root = getFilterRootByGroup(payload.group);
      if (!$root.length) return;
      storeInitialState($root);
      NS.initPriceSliders($root);
      NS.applyAttrVisibilityMode(payload.group);
    });

  $(function () {
    bindTitlebarToggle();
    bindPriceSlider();
    bindApplyReset();
    bindResizeVisibility();
    initRoots();
  });
})(jQuery);