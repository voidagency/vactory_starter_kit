/**
 * @file
 * Attaches the behaviors for the Banner module.
 */

(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.vactoryBanner = {
    attach: function attach(context, settings) {
      $(context)
        .find('.vf-banner__image')
        .each(Drupal.vactoryBannerLoad);
    }
  };

  Drupal.vactoryBannerLoad = function () {
    var $me = $(this);
    var bg_image = $me.data('desktop'),
      bg_image_mobile = $me.data('mobile');

    if ((window.matchMedia("(max-width: 991.98px)").matches)) {
      bg_image = (bg_image_mobile.length > 0) ? bg_image_mobile : bg_image;
    }

    $('<img src="' + bg_image + '"/>').on('load', function () {
      $me.css('background-image', 'url(' + bg_image + ')');
      $me.removeClass('loading');
      $(this).remove();
    });

  };

})(jQuery, Drupal);
