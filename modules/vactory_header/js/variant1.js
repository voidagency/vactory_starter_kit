/**
 * @file
 * Vactory Header Variant 1.
 */

(function ($, Drupal) {

  'use strict';

  // Menu.
  var cbpHorizontalMenu = (function () {

    var $listItems,
      $menuItems,
      $body = $('body'),
      current = -1;

    function init() {
      $listItems = $('#cbp-hrmenu > ul > li');
      $menuItems = $listItems.children('a');

      $menuItems.on('click', open);
      $listItems.on('click', function (event) {
        event.stopPropagation();
      });
    }

    function open(event) {
      if (current !== -1) {
        $listItems.eq(current).removeClass('cbp-hropen');
      }

      var $item = $(event.currentTarget).parent('li'),
        idx = $item.index();
      if (current === idx) {
        $item.removeClass('cbp-hropen');
        current = -1;
      }
      else {
        $item.addClass('cbp-hropen');
        current = idx;
       // $body.off('click').on('click', close);
      }

      // Has sub-menu.
      if ($($item).find("[class*='menu-sub']").length) {
        event.preventDefault();
        return false;
      }
    }

    function close(event) {
      $listItems.eq(current).removeClass('cbp-hropen');
      current = -1;
    }

    return {init: init};

  })();

  // Mobile menu.
  var mobileMenu = (function () {

    var $vhm_menu = $('#vhm');

    function init() {
      $vhm_menu.offcanvas();
      subMenu();
    }

    function subMenu() {
      $vhm_menu.find(".has-sub > a").click(function (e) {
        e.preventDefault();

        var selected = $(this);
        var submenu = selected.next('[class^="menu-sub--"]');

        if (submenu.css('display') === 'block') {
          selected.removeClass('selected');
          submenu.slideUp();

          submenu.find('.has-sub > a').removeClass('selected');
          submenu.find('[class^="menu-sub--"]').slideUp();
        }
        else {
          selected.addClass('selected');
          submenu.slideDown();
        }
      });
    }

    return {init: init};
  })();

  //== Init.
  $(document).ready(function () {
    cbpHorizontalMenu.init();
    mobileMenu.init();
  });

}(jQuery, Drupal));
