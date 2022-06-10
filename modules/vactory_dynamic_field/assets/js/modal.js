/**
 * @file
 * Javascript file for vactory_dynamic_field
 */

(function ($, window, Drupal) {
  Drupal.behaviors.vactory_dynamic_field_modal = {
    attach: function attach(context) {
      if (context !== document) {
        return;
      }
      $("#widgets-accordion", context).accordion({
        collapsible: true,
        heightStyle: "content"
      });
    }
  }
})(jQuery, window, Drupal);
