(function ($, Drupal) {
  "use strict";

  $(document).ready(function () {


    var $slider = $('.vf-slider--timeline .swiper-container');

    $slider.each(function (index, item) {

      var $item = $(this).addClass('swiper-container-' + index),
        yearsBullets = [],
        timelineDirection = ($item.data('direction') !== undefined) ? $item.data('direction') : 'vertical';

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

      $item.find('.swiper-slide').each(function () {
        yearsBullets.push($(this).data('year'));
      });


      var targetClass = '.vf-slider--timeline .swiper-container.swiper-container-' + index,
        $settings_swiper = {
          direction: timelineDirection,
          loop: true,
          speed: 1600,
          pagination: {
            el: '.swiper-pagination-' + index,
            clickable: true,
            type: 'bullets',
            renderBullet: function (index, className) {
              var year =  yearsBullets[index];
              return '<span class="' + className + '">' + year + '</span>';
            }
          },
          navigation: {
            nextEl: '.swiper-button-next-' + index,
            prevEl: '.swiper-button-prev-' + index,
          },
          breakpoints: {
            992: {
              direction: timelineDirection,
            },
            320: {
              direction: 'horizontal',
            }
          },
          preloadImages: false,
          lazy: true,
        };


      var timelineSwiper = new Swiper(targetClass, $settings_swiper);
    })

  });


})(jQuery, Drupal);
