//== UniteGallery.
//
//## Tiles Nested.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryTilesNested = {
    attach: function (context, settings) {
      jQuery("#gallery--tiles-nested").unitegallery({
        gallery_theme: "tiles",
        tiles_type:"nested"
      });
    }
  };
})(jQuery, Drupal);
