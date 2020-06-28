/**
 * @file
 * Vactory Header Variant 1.
 */

(function ($, Drupal) {

  'use strict';

  //== Init.
  $(document).ready(function () {

    // Open/Close Menu Desktop
    var classToOpen = " > .menu-wrapper > div > div > .fixed-mobile"; // ".menu-level--2"
    $('.vh-variant3 .menu__link, .vhm-variant3 .menu__link').on('click', function (e) {

      $(this).parent().hasClass('has-sub') ? e.preventDefault() : null;
      if ($(this).hasClass('is-open')) {
        $(this).removeClass('is-open').addClass('is-closed');
      }
      else {
        $(this).parent().siblings().find('> .menu__link').removeClass('is-open').addClass('is-closed');
        $(this).removeClass('is-closed').addClass('is-open');
      }
      if ($(this).parent().find(classToOpen).hasClass('open')) {
        $(this).parent().find(classToOpen).removeClass('open');
      }
      else {
        $(this).parent().find(classToOpen).addClass('open');
        if ($(this).parent().siblings().find(classToOpen).hasClass('open')) {
          $(this).parent().siblings().find(classToOpen).removeClass('open');
        }
      }
    });

    // Close mega menu
    $('.vh-variant3 .vh-mega-menu-close').on('click', function (e) {
      e.preventDefault();
      $(this).parents('.menu-item-wrapper').removeClass('open');
      $(this).parents('.menu__item--1').find('> .menu__link').removeClass('is-open').addClass('is-closed');
    });

    // go back for mega menu of wysiwyg
    $('.vh-mega-menu-mobile-back > a').on('click', function (e) {
      e.preventDefault();
      $(this).parents('.fixed-mobile').removeClass('open');
    });

    // close mega menu Mobile
    $('.block-menu-close a').on('click', function (e) {
      e.preventDefault();
      ($('.menu__item--1 .menu-level--2').hasClass('open')) ? $('.menu__item--1 .menu-level--2.open').removeClass('open') : null;
    });
    $(document).on('keyup', function (key) {
      if (key.keyCode == 27 && $('.menu__item--1 .menu-level--2').hasClass('open')) {
        $('.menu__item--1 .menu-level--2.open').removeClass('open');
      }
    });

    // Open/Close Menu Mobile
    $('#vhm-hamburger-btn, #mmenu-close-btn').on('click', function (e) {
      e.preventDefault();
      if ($('.vhm-variant3').hasClass('is-closed')) {
        $('.vhm-variant3').removeClass('is-closed').addClass('is-open');
        $('#vhm-hamburger-btn, #mmenu-close-btn').addClass('is-active');
        $('body').addClass('overflow-y');
      }
      else {
        $('.vhm-variant3').removeClass('is-open').addClass('is-closed');
        ($('.fixed-mobile').hasClass('open')) ? $('.fixed-mobile.open').removeClass('open') : null;
        $('#vhm-hamburger-btn, #mmenu-close-btn').removeClass('is-active');
        $('body').removeClass('overflow-y');
      }

      if ($('body').hasClass('simulator-open')) {
        $('.vh-header--simulator-closer > a').trigger('click');
      }
    });

    // Close mega menu when clicking out of menu
    $('body').on('click touchstart', function (e) {
      if (!$(e.target).parents('.vh-header--menu').length) {
        if ($('.vh-header .fixed-mobile').hasClass('open')) {
          $('.vh-header .fixed-mobile').removeClass('open');
          $('.menu__item--1 > .menu__link').removeClass('is-open').addClass('is-closed');
        }
      }
    });
    $(window).resize(function (event) {
      if (matchMedia('(min-width: 992px)').matches) {
        $('body').removeClass('overflow-y');
      }
    });


    headerHelpers.indicatorScroll(); // Call function to  show scroll indicator
    headerHelpers.stickyHeader();

  });

}(jQuery, Drupal));
