//== UniteGallery.
//
//## Tiles Justified - With Links.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryTilesJustifiedWithLinks = {
    attach: function (context, settings) {
      jQuery("#gallery--tiles-justified-with-links").unitegallery({
        gallery_theme: "tiles",
        tiles_type: "justified",
        tile_border_color: "#F0F0F0",
        tile_outline_color: "#8B8B8B",
        tile_enable_shadow: true,
        tile_shadow_color: "#8B8B8B",
        tile_show_link_icon: true,
        lightbox_textpanel_title_color: "e5e5e5",
        theme_gallery_padding: 20,
        tiles_justified_space_between: 20,
        tiles_justified_row_height: 200
      });
    }
  };
})(jQuery, Drupal);
