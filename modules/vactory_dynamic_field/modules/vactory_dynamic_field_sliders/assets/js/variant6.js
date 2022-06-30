(function ($, Drupal) {
  "use strict";

  $(document).ready(function () {

    var $slider = $('.vf-slider--fadein'),
      autoplayTime = 6000,
      targetdesktop = '.vf-slider--fadein-item.active h2 .vertical-part',
      targetMobile = '.vf-slider--fadein-item.active h2 .word-part',
      isLoad = true,
      animatedObj = targetdesktop;

    $(window).on('resize', function () {
      animatedObj = (window.matchMedia("(min-width: 992x)")) ? targetdesktop : targetMobile;
    });

    var fadeInSlide = function ($slide, target) {
      $slide.find('.vf-fadein-dots li').eq(target - 1).addClass('active').siblings().removeClass('active');
      $slide.find('.vf-slider--fadein-item').eq(target - 1).addClass('active').siblings().removeClass('active');
      $slide.find('.vf-slider--fadein-item').siblings().find('.vertical-part').removeAttr('style');
      animateSliderContent($slide, targetdesktop, targetMobile);
    };

    var animateSliderContent = function ($item, animateDesktop, animateMobile) {
      var animatedObj = (window.matchMedia("(min-width: 992x)")) ? animateDesktop : animateMobile;
      TweenMax.to($item.find(animatedObj), 0, {
        ease: Quart.easeIn,
        delay: 0,
        y: '50%',
        opacity: 0
      });
      var _timer = setTimeout(function () {
        $item.find(animatedObj).each(function (i, obj) {
          TweenMax.to(obj, .3, {
            ease: Back.easeOut,
            delay: i * .1,
            startAt: {y: '50%', opacity: 0},
            y: '0%',
            opacity: 1
          });
        });
        clearTimeout(_timer);
      }, 500);
    };

    var initSlider = function ($item, _activeIndex) {
      if (isLoad) {
        animateSliderContent($item, targetdesktop, targetMobile);
        isLoad = false;
      }
      fadeInSlide($item, _activeIndex);
      if ($item.data('autoplay') !== undefined && $item.data('autoplay')) {
        clearInterval(window.interval_);
        window.interval_ = setInterval(function () {
          var slideLength = $item.find('.vf-slider--fadein-item').length,
            itemActive = $item.find('.vf-slider--fadein-item.active').data('item');
          _activeIndex = (itemActive < slideLength) ? itemActive + 1 : 1;

          fadeInSlide($item, _activeIndex);
        }, autoplayTime);
      }

    };


    $slider.each(function (index, item) {
      var nextItem = 1;
      var $item = $(item);

      initSlider($item, nextItem);

      $item.find('.vf-fadein-dots li').on('click', function (e) {
        e.preventDefault();
        var itemActive = $item.find('.vf-slider--fadein-item.active').data('item');
        nextItem = $(this).data('item');
        if(itemActive !== nextItem) initSlider($item, nextItem);
      });

      $item.find('.vf-fadein-next').on('click', function (e) {
        e.preventDefault();
        var slideLength = $item.find('.vf-slider--fadein-item').length,
          itemActive = $item.find('.vf-slider--fadein-item.active').data('item');
        nextItem = (itemActive < slideLength) ? itemActive + 1 : 1;
        initSlider($item, nextItem);
      });

      $item.find('.vf-fadein-prev').on('click', function (e) {
        e.preventDefault();
        var slideLength = $item.find('.vf-slider--fadein-item').length,
          itemActive = $item.find('.vf-slider--fadein-item.active').data('item');
        nextItem = (itemActive > 1) ? itemActive - 1 : slideLength;
        initSlider($item, nextItem);
      });
    });

  });


})(jQuery, Drupal);
