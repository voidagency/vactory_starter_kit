(function ($, Drupal) {
  "use strict";

  $(document).ready(function () {

    var $slider = $('.vf-slider--parallax-effect .swiper-container');

    $slider.each(function (index, item) {

      var $item = $(this).addClass('swiper-container-' + index);

      if ($item.find('.swiper-button-next').length > 0) {
        $item.find('.swiper-button-next').addClass('swiper-button-next-' + index);
      }
      if ($item.find('.swiper-button-prev').length > 0) {
        $item.find('.swiper-button-prev').addClass('swiper-button-prev-' + index);
      }
      if ($item.find('.swiper-pagination').length > 0) {
        $item.find('.swiper-pagination').addClass('swiper-pagination-' + index);
      }
      if ($item.find('.swiper-scrollbar').length > 0) {
        $item.find('.swiper-scrollbar').addClass('swiper-scrollbar-' + index);
      }

      var targetClass = '.vf-slider--parallax-effect .swiper-container.swiper-container-' + index,
        $settings_swiper = {
          speed: 600,
          parallax: true,
          loop: true,
          keyboard: {
            enabled: true,
            onlyInViewport: false,
          },
          navigation: {
            nextEl: '.swiper-button-next-' + index,
            prevEl: '.swiper-button-prev-' + index,
          },
          pagination: {
            el: '.swiper-pagination-' + index,
            clickable: true
          }
        }


      var swiper = new Swiper(targetClass, $settings_swiper);
    });


  });


})(jQuery, Drupal);
