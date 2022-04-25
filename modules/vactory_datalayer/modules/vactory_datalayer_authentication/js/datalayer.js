(function ($, drupalSettings) {
  "use strict";
  $(document).ready(function () {
    var properties = drupalSettings['properties'];
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(
      properties
    );
  });
})(jQuery, drupalSettings)
