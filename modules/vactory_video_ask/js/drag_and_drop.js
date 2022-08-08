(function ($, window, Drupal) {
  Drupal.behaviors.DynamicFieldDragAndDrop = {
    attach: function attach() {
      $(document).ajaxStop(function() {
        var screensWrapper = document.getElementById('video-ask-screens-wrapper');
        var sortable = new Sortable(screensWrapper, {
          handle: '.va-screens-sortable-handler',
          animation: 150,
          onEnd: function (evt) {
            var screenWeights = $('.va-screens-weight');
            screenWeights.each(function(index) {
              $(this).val(index+1);
            });
          }
        });

      });
    }
  };
})(jQuery, window, Drupal);
