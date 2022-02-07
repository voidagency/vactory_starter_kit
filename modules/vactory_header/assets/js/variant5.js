/**
 * @file
 * Vactory Header Variant 1.
 */

(function ($, Drupal) {

  'use strict';

  //== Init.
  $(document).ready(function () {

    // Show menu on mobile
    $('.vh-variant5 .vh-hamburger .hamburger-box').on('click', function () {
      if ($(this).parents('.vh-layout').find('.vh-header--menu-wrapper').hasClass('open')) {
        $(this).parents('.vh-layout').find('.vh-header--menu-wrapper').removeClass('open');
        $(this).parent().removeClass('is-active');
        $('.vh-variant5 .vh-hamburger .hamburger--collapse').removeClass('is-active');
        $('body, html').removeClass('overflow-y');
      }
      else {
        $('body, html').addClass('overflow-y');
        $(this).parents('.vh-layout').find('.vh-header--menu-wrapper').addClass('open');
        $(this).parent().addClass('is-active');
        $('.vh-variant5 .vh-hamburger .hamburger--collapse').addClass('is-active');
      }
    });


    headerHelpers.indicatorScroll();
    headerHelpers.stickyHeader();
  });

}(jQuery, Drupal));
