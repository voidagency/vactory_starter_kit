//== UniteGallery.
//
//## Tiles Nested.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGallerySliderDisplay = {
    attach: function (context, settings) {

      var $gallery = $('#gallery-slider');


      $gallery.unitegallery({
        gallery_theme: "slider",
        slider_control_zoom: ($gallery.data('zoom') !== undefined) ? $gallery.data('zoom') : false,
      });
    }
  };
})(jQuery, Drupal);
