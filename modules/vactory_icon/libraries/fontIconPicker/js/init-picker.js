(function ($, Drupal) {
  Drupal.behaviors.vactoryIcons = {
    attach: function attach(context) {
      $(context).find('select.vactory--icon-picker').once('vactoryIconPicker').each(function (index, value) {
         $(value).fontIconPicker();
      });
    }
  };
})(jQuery, Drupal);
