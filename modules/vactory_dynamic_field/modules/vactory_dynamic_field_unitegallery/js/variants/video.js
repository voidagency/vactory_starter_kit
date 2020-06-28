//== UniteGallery.
//
//## Tiles Nested.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryVideoDisplay = {
    attach: function (context, settings) {

      var $gallery = $('#gallery-video');


      $gallery.unitegallery({
        gallery_theme: "video",
      });
    }
  };
})(jQuery, Drupal);
