//== UniteGallery.
//
//## Tiles Nested.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryCarouselDisplay = {
    attach: function (context, settings) {

      var $gallery = $('#gallery-carousel');


      $gallery.unitegallery({
        gallery_theme: "carousel",
      });
    }
  };
})(jQuery, Drupal);
