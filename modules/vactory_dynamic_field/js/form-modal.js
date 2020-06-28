/**
 * Dynamic Field Form Modal.
 **/

// Select2 search broken inside jQuery UI 1.10.x modal Dialog.
// URL: https://github.com/select2/select2/issues/1246
if (jQuery.ui && jQuery.ui.dialog && jQuery.ui.dialog.prototype._allowInteraction) {
  var ui_dialog_interaction = jQuery.ui.dialog.prototype._allowInteraction;
  jQuery.ui.dialog.prototype._allowInteraction = function(e) {
    if (jQuery(e.target).closest('.select2-dropdown').length) return true;
    return ui_dialog_interaction.apply(this, arguments);
  };
}

(function ($, Drupal) {
  Drupal.behaviors.DynamicFieldFormModal = {
    attach: function attach(context) {
      // Alter ckeditor wrapper CSS style after maximize event.
      CKEDITOR.on('instanceReady',
        function( evt ) {
          var editor = evt.editor;
          editor.on( 'afterCommandExec', function( evt ) {
            if ( evt.data.name == 'maximize' && evt.editor.mode == 'wysiwyg' ) {
              $('#dynamic-field-text-format[class=""]').attr('style', "position: fixed; overflow: visible; z-index: 9995; top: 0px; left: 0px;");
            }
          } );
        });
      $('.js-df-template-basic-single').select2();
      $('.template-filter').on('input', function () {
        userInput = $(this).val();
        templatesSet = $(this).closest('div')
          .siblings('.select-template-wrapper')
          .find('.select-template-item');

        templatesSet.each(function(index, value) {
          templateName = $(this).children('.template-name').text();
          templateName = templateName.toLowerCase();
          userInput = userInput.toLowerCase();
          if (templateName.search(userInput) > -1) {
            $(this).closest('div.form-item-template').show();
          }
          else {
            $(this).closest('div.form-item-template').hide();
          }
        });
      });
      $('.form-item-auto-populate').css('margin-left', '33px');
    }
  };
})(jQuery, Drupal);
