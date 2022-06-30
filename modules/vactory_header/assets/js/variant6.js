/**
 * @file
 * Vactory Header Variant 6.
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

  // ==============================================================
  // Header mobile menu show hide sub link
  // ==============================================================
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

  // ==============================================================
  // Resize all elements
  // ==============================================================
  $("body").trigger("resize");

  // ==============================================================
  //Fix header while scroll
  // ==============================================================
  // var wind = $(window);
  //
  // wind.on("load", function() {
  //   var bodyScroll = wind.scrollTop(),
  //     navbar = $(".vh-header-6");
  //   if (bodyScroll > 100) {
  //     navbar.addClass("fixed-header animated slideInDown")
  //   } else {
  //     navbar.removeClass("fixed-header animated slideInDown")
  //   }
  // });
  //
  // $(window).scroll(function() {
  //   if ($(window).scrollTop() >= 200) {
  //     $('.vh-header-6').addClass('fixed-header animated slideInDown');
  //   } else {
  //     $('.vh-header-6').removeClass('fixed-header animated slideInDown');
  //   }
  // });

  headerHelpers.indicatorScroll();
  headerHelpers.stickyHeader();

}(jQuery, Drupal));
