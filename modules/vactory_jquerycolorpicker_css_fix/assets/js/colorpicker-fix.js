(function ($, Drupal) {
  Drupal.behaviors.vactory_jquerycolorpicker_css_fix = {
    attach: function attach(context) {
      $(document).ajaxStop(function () {
        $(".colorpicker").addClass("show-colorpicker-form");
      });
    }
  };
})(jQuery, Drupal);
