/**
 * @file
 * JavaScript for Vactory Color Picker widget.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Syncs color display input with hidden input.
   */
  Drupal.behaviors.vactoryColorPicker = {
    attach: function (context, settings) {
      // Sync color_display changes to hidden input.
      once('vactory-color-picker-sync', 'input[type="color"][data-hidden-input-id]', context).forEach(function (element) {
        var $colorInput = $(element);
        var hiddenInputId = $colorInput.data('hidden-input-id');
        var $hiddenInput = $('#' + hiddenInputId);

        // When color input changes, update hidden input.
        $colorInput.on('input change', function () {
          var colorValue = $(this).val();
          $hiddenInput.val(colorValue).trigger('change');
        });

        // Initialize hidden input with color input value if hidden is empty.
        if (!$hiddenInput.val() && $colorInput.val()) {
          $hiddenInput.val($colorInput.val());
        }
      });
    }
  };

})(jQuery, Drupal, once);

