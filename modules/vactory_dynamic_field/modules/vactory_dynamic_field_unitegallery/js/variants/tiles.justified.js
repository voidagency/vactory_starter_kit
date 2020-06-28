//== UniteGallery.
//
//## Tiles Justified.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryTilesJustified = {
    attach: function (context, settings) {
      jQuery("#gallery--tiles-justified").unitegallery({
        gallery_theme: "tiles",
        tiles_type:"justified"
      });
    }
  };
})(jQuery, Drupal);
