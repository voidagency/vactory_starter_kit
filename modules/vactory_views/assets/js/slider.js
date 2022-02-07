/**
 * @file
 * vactory_views behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Add slider.
   */
  Drupal.behaviors.vactoryViewsSlider = {
    attach: function (context, settings) {

      var rtlMode = Drupal.vars.vactory.is_rtl,
        isTablette = (matchMedia("only screen and (max-width: 992px)").matches && matchMedia("only screen and (min-width: 768px)").matches) ? true : false,
        isSmallDevice = (matchMedia("only screen and (max-width: 767px)").matches) ? true : false,
        resizeTimer,
        prevArrow = Drupal.theme('vButtonMarkup', {
          'css': rtlMode ? 'slick-next' : 'slick-prev',
          'icon': rtlMode ? 'icon-chevron-right' : 'icon-chevron-left',
          'ariaLabel': rtlMode ? 'next-slide' : 'previous-slide'
        }),
        nextArrow = Drupal.theme('vButtonMarkup', {
          'css': rtlMode ? 'slick-prev' : 'slick-next',
          'icon': rtlMode ? 'icon-chevron-left' : 'icon-chevron-right',
          'ariaLabel': rtlMode ? 'previous-slide' : 'next-slide'
        });

      function initVactoryViewSlider() {
        $('.vactory-slider').each(function () {
          // Grab slider.
          var $slider = $(this);
          // Grab views slider settings.
          var $settings_views = $.parseJSON($slider.attr('data-settings'));
          // Default settings.
          var $settings_defaults = {
            dots: true,
            arrows: true,
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 3,
            cssEase: 'cubic-bezier(0.585, -0.005, 0.635, 0.920)',
            useTransform: true,
            accessibility: false,
            speed: 800,
            responsive: [
              {
                breakpoint: 992,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2,
                  centerMode: true,
                  centerPadding: '30px',
                  speed: 400,
                  cssEase: 'ease'
                }
              },
              {
                breakpoint: 768,
                settings: {
                  arrows: false,
                  dots: true,
                  slidesToShow: 1,
                  slidesToScroll: 1,
                  centerMode: false,
                  centerPadding: '30px',
                }
              }
            ],
            appendDots: $slider.next('.slider-dots'),
            nextArrow: nextArrow,
            prevArrow: prevArrow
          };

          $settings_views.responsive = [$settings_views.responsive, $settings_defaults.responsive[1]]; // fix json_encode

          // Merge views settings with defaults.
          var $settings = $.extend({}, $settings_defaults, $settings_views);
          if ($slider.find('> *').length > $settings.slidesToShow && !isTablette && !isSmallDevice) {
            (!$slider.hasClass('slick-initialized')) ? $slider.slick($settings) : $slider.slick('refresh');
          }
          // Initialize for tablette
          else if (isTablette && !isSmallDevice && ($slider.find('> *').length > $settings.responsive[0].settings.slidesToShow || $slider.find('.slick-slide:not(.slick-cloned)').length > $settings.responsive[0].settings.slidesToShow)) {
            (!$slider.hasClass('slick-initialized')) ? $slider.slick($settings) : $slider.slick('refresh');
          }
          // Initialize for smartphone
          else if (isSmallDevice && !isTablette && ($slider.find(" > *").length > 1 ||$slider.find(".slick-slide:not(.slick-cloned)").length > 1 )) {
            (!$slider.hasClass('slick-initialized')) ? $slider.slick($settings) : $slider.slick('refresh');
          }
          // Remove Slick slidre if theire not enough of items
          else if ((!isTablette && !isSmallDevice && $slider.find('.slick-slide:not(.slick-cloned)').length <= $settings.slidesToShow)
            || (isTablette && !isSmallDevice && $slider.find('.slick-slide:not(.slick-cloned)').length <= $settings.responsive[0].settings.slidesToShow)
          ) {
            if($slider.hasClass('slick-initialized')) {
              $slider.slick('destroy');
            }
          }
        });

      }

      initVactoryViewSlider();


      $(window).resize(function (e) {
        clearTimeout(resizeTimer);  // After finish resizing
        resizeTimer = setTimeout(function () {
          isTablette = (matchMedia("only screen and (max-width: 992px)").matches && matchMedia("only screen and (min-width: 768px)").matches) ? true : false;
          isSmallDevice = (matchMedia("only screen and (max-width: 767px)").matches) ? true : false;
          initVactoryViewSlider();
        }, 250);
      });
    }
  };

}(jQuery, Drupal));
