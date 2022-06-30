/**
 * @file
 * Vactory Header Helpers
 */
var headerHelpers = {};
(function ($, Drupal) {

  'use strict';

  headerHelpers = {
    vars: {
      headerTarget: $('.vh-sticky'), // default header target
      showHideOption: { // Options of sticky header
        FIXED: 'fixed',
        DOWN: 'down',
        ALL: 'all'
      },
      scrollIndicator: $('.vh-header__scroll-indicator'), // default indicateur target
      headerMobileMenu: $('.vhm-header__menu')
    },

    /**
     *
     * @param targetHeader  : (Optionel) jquery object for targetHeader.
     * @param optionShowHide : (Optionel) option to stickyheader on scroll or
     *     fixed header on scroll or do nothing (exemple of paramete :
     *     headerHelpers.vars.showHideOption.ALL).
     * @param offsetToSticky : (Optionel) The offset where add classes of
     *     stickyheader.
     */
    stickyHeader: function (targetHeader, optionShowHide, offsetToSticky) {
      // function of sticky header
      var lastScrollTop = $(window).scrollTop(),
        stickyHeader = (targetHeader === undefined) ? headerHelpers.vars.headerTarget : targetHeader,
        heightOfHeader = (offsetToSticky === undefined) ? stickyHeader.outerHeight(true) : offsetToSticky,
        optionShowHide = (optionShowHide === undefined) ? headerHelpers.vars.showHideOption.ALL : optionShowHide;

      $(window).on('scroll', function () {

        var windowScrollTop = $(this).scrollTop();
        // Set animation after changing position from absolute to fixed
        if (optionShowHide !== headerHelpers.vars.showHideOption.DOWN) {
          (windowScrollTop > (heightOfHeader + 30)) ? stickyHeader.addClass('transitioned') : stickyHeader.removeClass('transitioned');
          (windowScrollTop > (heightOfHeader + 30)) ? stickyHeader.addClass('vh-fixed') : stickyHeader.removeClass('vh-fixed');
        }

        if (windowScrollTop > heightOfHeader) {
          if (windowScrollTop < lastScrollTop) {
            if (optionShowHide === headerHelpers.vars.showHideOption.ALL || optionShowHide === headerHelpers.vars.showHideOption.FIXED) {
              stickyHeader.removeClass('bottom').addClass('top');
            }
          }
          else {
            //if (optionShowHide === headerHelpers.vars.showHideOption.ALL ||
            // optionShowHide === headerHelpers.vars.showHideOption.DOWN) {
            if (optionShowHide === headerHelpers.vars.showHideOption.ALL) {
              stickyHeader.removeClass('top').addClass('bottom');
            }
            else if (optionShowHide === headerHelpers.vars.showHideOption.FIXED) {
              stickyHeader.removeClass('bottom').addClass('top');
            }
          }
        }
        else {
          stickyHeader.removeClass('top').removeClass('bottom');
        }
        lastScrollTop = windowScrollTop;
      });
    },

    /**
     *
     * @param targetId : (Optionel) jquery object for scrollIndicator div
     */
    indicatorScroll: function (targetId) {
      var target = (targetId === undefined) ? headerHelpers.vars.scrollIndicator : targetId;
      if (target.length) {
        $(window).scroll(function () {
          var offsetTop = parseInt($(this).scrollTop()),
            parentHeight = parseInt($('body, html').height() - $(window).height()),
            vScrollWidth = offsetTop / parentHeight * 100;
          target.css({width: vScrollWidth + '%'});
        });
      }
    },

    /**
     * @param targetId : (Optionel) jquery object for header menu mobile div
     */
     mobileMenu: function (targetId) {
       var target = (targetId === undefined) ? headerHelpers.vars.headerMobileMenu : targetId;
       if (target.length) {
         target.find('li.has-sub > a').on('click', function (event) {
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
       }
     },

  }

}(jQuery, Drupal));
