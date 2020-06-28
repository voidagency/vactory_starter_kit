/**
 * @file
 * Javascript file for vactory_dynamic_field
 */

(function ($, window, Drupal) {
  Drupal.behaviors.vactory_dynamic_field_modal = {
    attach: function attach(context) {
      $("#widgets-accordion", context).accordion({
        collapsible: true,
        heightStyle: "content"
      });
    }
  }
})(jQuery, window, Drupal);
