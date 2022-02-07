/**
 * @file
 * Vactory Header Variant 4 - CDG Capital.
 */

(function ($, Drupal) {

  'use strict';

  //== Init.
  $(document).ready(function () {

    var isDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    $('.vh-hamburger .hamburger').on('click', function () {
      $('.vh-hamburger .hamburger').toggleClass('is-active');
      $('.layer-menu-mobile').toggleClass('is-open');
    });

    // Menu.
    $('#main-navigation-layer').offcanvas({
      modifiers: 'top, overlay',
      triggerButton: '#main-navigation-trigger',
      closeButtonClass: "main-navigation-close"
    });

    $('.vh-header--navigation .nav-item > .menu__link').on('click', function (e) {
      var _this = $(this),
        submenu = _this.next('.vh-header--menu-wrapper');
      if (submenu.length) {
        e.preventDefault();
        if (submenu.is(':visible')) {
          submenu.slideUp(function () {
            _this.parent().removeClass('opened');
          });
        }
        else {
          if (!isDevice && matchMedia('(max-width: 768px)').matches) {
            $('.vh-header--navigation .opened .vh-header--menu-wrapper').hide();
            $('.vh-header--navigation .nav-item.opened').removeClass('opened');
            submenu.slideDown();
          }
          else {
            $('.vh-header--navigation .opened .vh-header--menu-wrapper').slideUp();
            $('.vh-header--navigation .nav-item.opened').removeClass('opened');
            submenu.slideDown();
          }
          _this.parent().addClass('opened');
        }
      }
    });



    $(document).on('click', function (e) {
      if ($('.has-sub.opened').length && !isDevice && matchMedia('(max-width: 768px)').matches) {
        var menu_item = $('.has-sub');
        if (!menu_item.find('> .menu__link').is(e.target) && menu_item.has(e.target).length === 0) {
          $('.has-sub.opened > .menu__link').trigger('click');
        }
      }
    });

    //
    if (isDevice && matchMedia('(max-width: 768px)').matches) {
      $('.has-sub > a.is-active-trail').trigger('click');
    }


    // Call function to sticky header
    headerHelpers.stickyHeader();
  });

}(jQuery, Drupal));
