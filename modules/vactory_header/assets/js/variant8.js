/**
 * @file
 * Vactory Header Variant 8.
 */

(function($, Drupal) {

  'use strict';

  // ==============================================================
  // Trigger Class active for Hamburger
  // ==============================================================
  $('.vh-header__hamburger .hamburger').click(function() {
    $('.vh-header__hamburger .hamburger').toggleClass("is-active")
  });

  // ==============================================================
  // Trigger js-Offcanvas for mobile menu
  // ==============================================================
  $('#vhm').offcanvas({
    triggerButton: '#vhm-hamburger-btn' // btn to open offcanvas
  });


  headerHelpers.indicatorScroll();
  headerHelpers.stickyHeader();
  headerHelpers.mobileMenu();

}(jQuery, Drupal));
