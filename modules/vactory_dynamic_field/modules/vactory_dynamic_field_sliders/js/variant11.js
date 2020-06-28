(function ($, Drupal) {
  "use strict";

  $(document).ready(function () {


    $('.vf-slider-swiper').each(function (index, item) {


      var $item = $(this).find('.swiper-container').addClass('swiper-container-' + index)
      var $parent = $(this);

      if ($parent.find('.swiper-button-next').length > 0) {
        $parent.find('.swiper-button-next').addClass('swiper-button-next-' + index);
      }
      if ($parent.find('.swiper-button-prev').length > 0) {
        $parent.find('.swiper-button-prev').addClass('swiper-button-prev-' + index);
      }
      if ($parent.find('.swiper-pagination').length > 0) {
        $parent.find('.swiper-pagination').addClass('swiper-pagination-' + index);
      }
      if ($parent.find('.swiper-scrollbar').length > 0) {
        $parent.find('.swiper-scrollbar').addClass('swiper-scrollbar-' + index);
      }


      var targetClass = '.vf-slider-swiper .swiper-container.swiper-container-' + index,
        $swiper_settings = {
          effect: ($item.data('effect') !== undefined) ? $item.data('effect') : 'cube',
          direction: ($item.data('direction') !== undefined) ? $item.data('direction') : 'vertical',
          loop: ($item.data('loop') !== undefined) ? $item.data('loop') : true,
          keyboard: {
            enabled: ($item.data('keyboard') !== undefined) ? $item.data('keyboard') : true,
          },
          speed: ($item.data('animationspeed') !== undefined) ? $item.data('animationspeed') : 500,
          navigation: {
            nextEl: '.swiper-button-next-' + index,
            prevEl: '.swiper-button-prev-' + index,
          },
          pagination: {
            el: '.swiper-pagination-' + index,
            clickable: true,
          },
          //slideClass: 'swiper-slide',
          //slideActiveClass: 'swiper-slide-active',
          //slideVisibleClass: 'swiper-slide-visible',
          autoHeight: false,
          centeredSlides: true,
          preloadImages: false,
          lazy: true,
          //spaceBetween: 30,
        };

      if ($item.data('effect') !== undefined && $item.data('effect') !== 'fade' && $item.data('effect') !== "cube" && $item.data('effect') !== 'flip') {
        $swiper_settings.slidesPerView = ($item.data('numbreslidesmobile') !== undefined) ? $item.data('numbreslidesmobile') : 1;
        $swiper_settings.breakpoints = {
          320: {
            slidesPerView: ($item.data('numbreslidesmobile') !== undefined) ? $item.data('numbreslidesmobile') : 1,
          },
          767: {
            slidesPerView: 2,
          },
          992: {
            slidesPerView: ($item.data('numbreslides') !== undefined) ? $item.data('numbreslides') : 1,
          },
        }
      }
      else {
        $swiper_settings.slidesPerView = 1;
      }

      if ($item.data('scrollbar') !== undefined && $item.data('scrollbar') == true) {
        $swiper_settings.scrollbar = {
          el: '.swiper-scrollbar-' + index,
          draggable: true,
        }
      }

      var swiper = new Swiper(targetClass, $swiper_settings);

      swiper.on('slideChangeTransitionEnd sliderMove', function () {
        $(item).find('.swiper-slide .swiper-content > *').removeClass('fadeInUp animated');
        $(item).find('.swiper-slide-active .swiper-content > *').addClass('fadeInUp animated');
      })
    })

  });


})(jQuery, Drupal);
