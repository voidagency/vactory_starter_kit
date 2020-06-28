//## Apply Muuri
(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.annual_report_grid = {
    attach: function (context, settings) {
      // check if document == context.
      var gridElement = document.getElementById("js--wrapper-annual-grid");
      if (document.body.contains(gridElement)) {
        var is_rtl = !!($('html[dir="rtl"]').length), annualReportGrid;
        annualReportGrid = new Muuri('.annual-grid', {
          dragEnabled: false,
          layout: {
            fillGaps: true,
            alignRight: is_rtl,
            rounding: true
          }
        });
      }
    }
  };
})(jQuery, Drupal);
