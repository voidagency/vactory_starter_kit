/**
 * @file
 * Javascript file for drag and drop feature for vactory_dynamic_field.
 */

(function ($, window, Drupal) {
  Drupal.behaviors.DynamicFieldDragAndDrop = {
    attach: function attach() {
      $(document).ajaxStop(function() {
        var component = document.getElementById('sortable-components');
        var sortable = new Sortable(component, {
          handle: '.df-components-sortable-handler',
          animation: 150,
          onEnd: function (evt) {
            var componentsWeights = $('.df-components-weight');
            componentsWeights.each(function(index) {
              $(this).val(index+1);
            });
          }
        });

      });
    }
  }
})(jQuery, window, Drupal);
