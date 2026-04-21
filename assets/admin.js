jQuery(function ($) {
  const url = new URL(window.location.href);
  if (url.searchParams.get('saved') === '1') {
    $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>Saved.</p></div>');
  }

  function initSortable($root) {
    if (!$root || !$root.length) return;
    if (!$.fn.sortable) return;

    $root.sortable({
      handle: '.gwsfb-sort-handle',
      placeholder: 'gwsfb-sort-placeholder',
      update: function () {
        const order = [];
        $root.children('[data-key]').each(function () {
          order.push(String($(this).attr('data-key')));
        });
        const $target = $($root.attr('data-order-target'));
        if ($target.length) {
          $target.val(order.join(','));
        }
      }
    });
  }

  $('.gwsfb-admin-sortable').each(function () {
    initSortable($(this));
  });
});