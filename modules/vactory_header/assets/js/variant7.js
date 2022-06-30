/**
 * @file
 * Vactory Header Variant 7.
 */

(function($, Drupal) {

  'use strict';

  // ==============================================================
  // Trigger Class active for Hamburger
  // ==============================================================
  $('.vh-hamburger .hamburger').click(function() {
    $(this).toggleClass("is-active")
  });

  // ==============================================================
  // Trigger js-Offcanvas for mobile menu
  // ==============================================================
  $('#vhm').offcanvas({
    triggerButton: '#vhm-hamburger-btn' // btn to open offcanvas
  });

  $('.vhm-header__menu li.has-sub > a').on('click', function (event) {
    event.preventDefault();
    var selected = $(this);
    var submenu = selected.next('.menu-wrapper');
    if (submenu.css('display') == 'block') {
      selected.removeClass('expanded');
      submenu.slideUp();
      submenu.find('a').removeClass('expanded');
    } else {
      selected.addClass('expanded');
      submenu.slideDown();
      selected.parent().siblings().find('.menu-wrapper').slideUp();
      selected.parent().siblings().find('a').removeClass('expanded');
    }
  });

  // ==============================================================
  // For mega menu
  // ==============================================================
  $(document).on('click', '.mega-dropdown', function(e) {
    e.stopPropagation();
  });

  // $(document).on('click', '.navbar-nav > .dropdown', function(e) {
  //   e.stopPropagation();
  //   console.log('click stop');
  // });

  $(".dropdown-submenu.has-sub").click(function(e) {
    e.preventDefault();
    $(this).find(" > .dropdown-menu").toggleClass("show");
  });


  headerHelpers.indicatorScroll();
  headerHelpers.stickyHeader();

}(jQuery, Drupal));
