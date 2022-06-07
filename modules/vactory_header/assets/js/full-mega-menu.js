(function ($, Drupal) {
    $(function () {
      'use strict';
      var vh_header = $('.vh-header');
      var vh_header_menu = $('.full-mega-menu');
      var _header_burger = $('.vh-header__hamburger .hamburger');
      var _menu_sub_trigger = vh_header_menu.find('.menu__item.has-sub > .menu__link');
      var _menu_sub_trigger_desktop = vh_header_menu.find('.menu__item--1.has-sub .menu__link--1');
  
      var close_all_menu_mobile_dropdown = function() {
        vh_header_menu.find('.open').removeClass('open');
        vh_header_menu.find('.menu__item.has-sub > div > div > ul').stop(true, true).slideUp(500);
      };
  
      var open_close_menu_mobile = function() {
        if(vh_header.hasClass('menu-mobile-open')) {
          vh_header.removeClass('menu-mobile-open');
          vh_header_menu.removeClass('is-open');
          $('body').removeClass('overflow-y');
          _header_burger.removeClass('is-active');
          close_all_menu_mobile_dropdown();
        } else {
          vh_header.addClass('menu-mobile-open');
          vh_header_menu.addClass('is-open');
          $('body').addClass('overflow-y');
          _header_burger.addClass('is-active');
        }
      };
  
      if (matchMedia("only screen and (min-width:768px)").matches) {
  
        _menu_sub_trigger_desktop.on('click', vh_header_menu, function (ev, el) {
          ev.preventDefault();
  
          var _link_clicked = $(this);
          var _link_parent = $(this).parent();
          var _link_sub_menu = $(this).siblings('.menu__sub--1');
  
          if (_link_clicked.hasClass('open')) {
            _link_sub_menu.slideUp(400, function () {
              _link_clicked.removeClass('open');
              _link_parent.removeClass('open');
            });
          } else {
            if (_link_parent.siblings().hasClass('open')) {
              var _link_parent_siblings = _link_parent.siblings();
              _link_parent_siblings.each(function(i, e) {
                if($(this).hasClass('open')) {
                  $(this).find('.menu__sub--1').slideUp(300, function () {
  
                    _link_parent_siblings.removeClass('open');
                    _link_parent_siblings.find('.open').removeClass('open');
  
                    _link_parent.addClass('open');
                    _link_clicked.addClass('open');
                    _link_parent.find('.menu__sub--1').stop(true, true).slideDown(300);
                  });
                }
              });
            } else {
              _link_clicked.addClass('open');
              _link_parent.addClass('open');
              _link_sub_menu.stop(true, true).slideDown(400);
            }
          }
        });
  
        $('.menu__sub--closer').on('click', vh_header_menu, function(ev, el) {
          ev.preventDefault();
          $(this).parents('.menu__sub--1').slideUp(500, function () {
            $(this).parents('.menu__item--1').removeClass('open').find('.menu__link--1').removeClass('open');
          });
        });
      }
  
      if (matchMedia("only screen and (max-width:767px)").matches) {
  
        _header_burger.on('click', vh_header_menu, function(ev, el) {
          ev.preventDefault();
          open_close_menu_mobile();
        });
  
        _menu_sub_trigger.on('click', vh_header_menu, function(ev, el) {
          ev.preventDefault();
  
          var _link_clicked = $(this);
          var _link_parent = $(this).parent();
          var _link_sibling_sub = $(this).siblings( "div" ).find('> div > ul');
  
          if (_link_clicked.hasClass('open')) {
  
            _link_sibling_sub.stop(true, true).slideUp(500, function () {
              _link_clicked.removeClass('open');
              _link_parent.removeClass('open');
            });
  
          } else {
  
            if (_link_parent.siblings().hasClass('open')) {
              var _link_parent_siblings = _link_parent.siblings();
              _link_parent_siblings.each(function(i, e) {
                if($(this).hasClass('open')) {
                  $(this).find('> div > div > ul').slideUp(500, function () {
  
                    _link_parent_siblings.removeClass('open');
                    _link_parent_siblings.find('.open').removeClass('open');
                    _link_parent_siblings.find('ul').stop(true, true).slideUp(500);
  
                    _link_parent.addClass('open');
                    _link_clicked.addClass('open');
                    _link_parent.find('> div > div > ul').stop(true, true).slideDown(500);
                  });
                }
              });
            } else {
              _link_clicked.addClass('open');
              _link_parent.addClass('open');
              _link_parent.find('> div > div > ul').stop(true, true).slideDown(500);
            }
          }
  
        });
      };
      
      headerHelpers.stickyHeader();

    });
  })(jQuery, Drupal);
  