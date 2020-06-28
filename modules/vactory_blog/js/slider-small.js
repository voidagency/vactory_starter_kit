/**
 * @file
 * vactory_news behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Add slider.
   */
  Drupal.behaviors.vactoryBlogsSliderSmall = {
    attach: function (context, settings) {

      var $slider = $('.vactory-blogs--slider-small');

      $slider.slick({
        dots: true,
        arrows: true,
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 3,
        cssEase: 'cubic-bezier(0.585, -0.005, 0.635, 0.920)',
        useTransform: true,
        speed: 800,
        responsive: [
          {
            breakpoint: 992,
            settings: {
              slidesToShow: 1,
              centerMode: true,
              centerPadding: '35px',
              speed: 400,
              cssEase: 'ease'
            }
          }
        ],
        appendDots: $slider.next('.slider-dots'),
        nextArrow: '<button type="button" class="slick-arrow next"><i class="icon-chevron-right"></i></button>',
        prevArrow: '<button type="button" class="slick-arrow prev"><i class="icon-chevron-left"></i></button>'
      });

    }
  };

}(jQuery, Drupal));
