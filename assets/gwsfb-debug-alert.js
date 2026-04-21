(function (window) {
  'use strict';

  window.GWSFB_DEBUG_ALERT = window.GWSFB_DEBUG_ALERT || {
    enabled: true,
    show: function (title, payload) {
      if (!this.enabled) return;

      var msg = String(title || 'GWSFB DEBUG');

      if (typeof payload !== 'undefined') {
        try {
          if (typeof payload === 'string') {
            msg += '\n\n' + payload;
          } else {
            msg += '\n\n' + JSON.stringify(payload, null, 2);
          }
        } catch (e) {
          msg += '\n\n' + String(payload);
        }
      }

      alert(msg);
    }
  };
})(window);