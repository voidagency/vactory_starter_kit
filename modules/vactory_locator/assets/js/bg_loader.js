
jQuery(document).ready(function($) {
    var _bgWrapper = $('.background_image');
    if (_bgWrapper.length) {
      $(_bgWrapper).each(function(i, value) {
        var bg_image = $(value).data('desktop');
  
        if((window.matchMedia("(max-width: 767.98px)").matches)) {
          bg_image = ($(value).data('mobile').length > 0) ? $(value).data('mobile') : $(value).data('desktop');
        }
  
        $('<img src="'+ bg_image+'"/>').on('load', function(){
          $(value).css('background-image', 'url('+bg_image+')');
          $(this).remove();
          $(value).removeClass('loading');
        });
      });
    }
  });
  