/**
 * @file
 * JavaScript for Vactory Color Picker widget.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Syncs color display input with hidden input and handles clear button.
   */
  Drupal.behaviors.vactoryColorPicker = {
    attach: function (context, settings) {
      // Handle color input changes.
      once('vactory-color-picker-sync', 'input.vactory-color-picker-input', context).forEach(function (element) {
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

      // Handle clear link clicks.
      once('vactory-color-picker-clear', '.vactory-color-picker-clear', context).forEach(function (element) {
        var $clearLink = $(element);
        var colorInputId = $clearLink.data('color-input-id');
        var hiddenInputId = $clearLink.data('hidden-input-id');
        var defaultColor = $clearLink.data('default-color') || '#FF0000';
        var $colorInput = $('#' + colorInputId);
        var $hiddenInput = $('#' + hiddenInputId);

        $clearLink.on('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          // Clear the hidden input (empty value = NULL = delete from database).
          $hiddenInput.val('').trigger('change');
          // Reset the display color input to default color.
          $colorInput.val(defaultColor);
          return false;
        });
      });
    }
  };

})(jQuery, Drupal, once);

