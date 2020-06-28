//== UniteGallery.
//
//## Tiles Columns - Videos.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.UniteGalleryTilesColumnsVideos = {
    attach: function (context, settings) {
      jQuery("#gallery--tiles-columns-videos").unitegallery({
        gallery_theme: "tiles",
        gallery_width:"960",
        tile_enable_border:true,
        tile_border_color:"#ffffff",
        tile_enable_outline:true,
        tile_outline_color:"#b6b6b6",
        tile_shadow_color:"#8B8B8B",
        tile_overlay_opacity:0.6,
        tile_enable_image_effect:true,
        tile_image_effect_type:"blur",
        tile_image_effect_reverse:true,
        tile_enable_textpanel:true,
        tile_textpanel_bg_color:"#332e68",
        tile_textpanel_bg_opacity:0.9,
        tile_textpanel_title_text_align:"center",
        lightbox_textpanel_enable_title:false,
        lightbox_textpanel_enable_description:true,
        lightbox_textpanel_desc_color:"e5e5e5",
        tiles_col_width:200,
        tiles_space_between_cols:30
      });
    }
  };
})(jQuery, Drupal);
