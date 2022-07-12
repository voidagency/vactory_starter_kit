(function ($, Drupal) {
  "use strict";

  // Variables
  var _slider = $('.vf-slider--full-background .slider');

  $(document).ready(function () {

    var $slider = $('.vf-slider.vf-slider--full-background'),
      isDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    $slider.each(function (index, item) {

      $(item).find('.background').each(function (i, value) {
        var bg_image = $(value).data('desktop');
        if ((window.matchMedia("(max-width: 992px)").matches) || isDevice) {
          bg_image = ($(value).data('mobile').length > 0) ? $(value).data('mobile') : $(value).data('desktop');
        }

        $('<img src="' + bg_image + '" />').on('load', function () {
          $(value).css('background-image', 'url(' + bg_image + ')');
          $(this).remove();
          $(value).removeClass('loading');
        })
      });

      if ($(item).find('.vf-slick-slider > *').length === 1) {
        $(item).find('.vf-slick-slider > *').find('.slider-content > *').addClass('animated fadeInUp animate-end');
      }
    });


    //set animation on init of slick slider
    _slider.on('init', function (event, slick) {
      _slider.find('.slick-current').find('.slider-content > *').addClass('animated fadeInUp animate-end');
    });

    // Set animation on change of element
    _slider.on('afterChange', function (event, slick, currentSlide) {
      _slider.find('.slick-slide').eq(currentSlide + 1).find('.slider-content > *').addClass('animated fadeInUp animate-end');
      _slider.find('.slick-slide').eq(currentSlide + 1).siblings().find('.slider-content > *').removeClass('animated fadeInUp  animate-end');

      if ((!isDevice || matchMedia('(min-width: 992px)').matches) && _slider.find('.slick-slide').find('.background.is-video').length > 0) {
        if (_slider.find('.slick-slide').eq(currentSlide + 1).find('.background.is-video').next().hasClass('mb_YTPlayer')) {
          _slider.find('.slick-slide').find('.background.is-video').next().YTPPause();
        }
        if (_slider.find('.slick-slide').eq(currentSlide + 1).find('.background').hasClass('is-video')) {
          _slider.find('.slick-slide').eq(currentSlide + 1).find('.background').next().YTPPlay();
        }
      }

    });
  });


})(jQuery, Drupal);
