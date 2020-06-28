// @todo: ES6 because Drupal 8.4.x use it

(function ($) {

  $.fn.showMenu = function (options) {

    //default vars for the plugin
    var opts = $.extend({}, $.fn.showMenu.defaults, options);

    return $(this).on('click', function (e) {
      e.preventDefault();
      var $slc = $(this).parent('.expanded');
      $slc.toggleClass(opts.toggleClassOpen);
      $slc.find(".wrapper__menu.level-1").toggleClass(opts.toggleClassOpen);
    });

  };
  // Overridable defaults
  $.fn.showMenu.defaults = {
    toggleClassOpen: "is-open"
  };

  //
  $.fn.cloneMenu = function (options) {

    //default vars for the plugin
    var opts = $.extend({}, $.fn.cloneMenu.defaults, options);

    $(".vh__mobile .wrapper__menu").addClass('collapse');

    return $(this).on('click', function (e) {
      e.preventDefault();
      //$("html").toggleClass(opts.toggleClassOpen);
      //$(".vh__header.vh__header-2.vh__desktop").toggleClass(opts.toggleClassDesktop);
      // $(".vh__header.vh__header-2.vh__mobile").toggleClass(opts.toggleClassMobile);
      $(".vh__desktop .main-menu").clone().appendTo(".vh__mobile .vh__main-menu");
      console.log('ok');
    });

  };
  $.fn.cloneMenu.defaults = {
    toggleClassBody: "screen-disable__scroll",
    toggleClassDesktop: "vh__visible-d",
    toggleClassMobile: "vh__visible-m"
  };

})(jQuery);
