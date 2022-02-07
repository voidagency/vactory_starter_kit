/**
 * @file
 * Vactory Header Variant 2.
 */

(function($, Drupal) {

  'use strict';

  // ==============================================================
  // Trigger Class active for Hamburger
  // ==============================================================
  $('.hamburger').click(function() {
    $('.hamburger').toggleClass("is-active");
    $('html').toggleClass('menu-open');
    $('#vhm').toggleClass('is-closed');
  });



  headerHelpers.mobileMenu();
  headerHelpers.indicatorScroll();
  headerHelpers.stickyHeader();

}(jQuery, Drupal));
