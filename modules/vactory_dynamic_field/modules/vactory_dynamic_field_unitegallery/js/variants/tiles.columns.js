//== UniteGallery.
//
//## Tiles Columns.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryTilesColumns = {
    attach: function (context, settings) {
      jQuery("#gallery--tiles-columns").unitegallery({
        gallery_theme: "tiles",
      });
    }
  };
})(jQuery, Drupal);
