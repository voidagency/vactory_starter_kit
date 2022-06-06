//@file header-1.drupal.js

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.header = {
    attach: function (context) {
      $('.vf-header--variant1', context).header1({
        // @todo: expose interface data
        breakpoint: drupalSettings.interface.breakpoints
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
