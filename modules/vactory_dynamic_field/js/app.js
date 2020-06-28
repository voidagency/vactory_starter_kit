/**
 * @file
 * Javascript file for vactory_dynamic_field
 */

(function ($, window, Drupal) {
  Drupal.behaviors.vactory_dynamic_field = {
    attach: function attach() {
      $(document).ajaxComplete(function() {
        // Check if updating templates weights & deltas is required.
        if ($('.update-templates-deltas').length) {
          var addMore = $('fieldset.update-templates-deltas').attr('add-more-button');
          if (addMore) {
            $('input[name="' + addMore + '"]').trigger('mousedown');
          }
        }

        if ($('fieldset.multiple-choose-template').length) {
          var isFirstFounded = false;
          $('.field--type-field-wysiwyg-dynamic').each(function(i, el){
            isFirstFounded = false;
            $(el).find('.multiple-choose-template').closest('tbody').find('tr').each(function(index, item){
              if($(item).find('.multiple-choose-template').length > 0){
                if(!isFirstFounded) {
                  isFirstFounded = true;
                }
                else {
                  $(item).find('.multiple-choose-template').closest('tr').hide();
                }
              }

            })
          })
        }
      });
    }
  }
})(jQuery, window, Drupal);
