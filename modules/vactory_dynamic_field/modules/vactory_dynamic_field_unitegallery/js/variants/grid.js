//== UniteGallery.
//
//## Tiles Nested.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryGridDisplay = {
    attach: function (context, settings) {

      var $gallery = $('#gallery-grid');

      $gallery.unitegallery({
        gallery_theme: "grid",
        theme_panel_position: $gallery.data('position'),
        slider_control_zoom: ($gallery.data('zoom') !== undefined) ? $gallery.data('zoom') : false,
        gridpanel_vertical_scroll: ($gallery.data('arrows-vertical') !== undefined) ? $gallery.data('arrows-vertical') : false, /* it work if position is left or right by seting arrows at the bottom */
        gridpanel_grid_align: "top",
        grid_num_cols: ($gallery.data('gridnumcols') !== undefined) ? $gallery.data('gridnumcols') : 2,
        slider_enable_arrows: ($gallery.data('arrowsenable') !== undefined) ? $gallery.data('arrowsenable') : false,
        slider_enable_text_panel: ($gallery.data('disabletext') !== undefined) ? $gallery.data('disableText') : false,
      });
    }
  };
})(jQuery, Drupal);
