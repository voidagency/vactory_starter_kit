// @todo: ES6 because Drupal 8.4.x use it
// @file header-1.js

(function ($) {

  // Overridable defaults
  $.fn.header1.defaults = {
    breakpoint: 700,
    toggleClass: 'header__toggle',
    toggleClassActive: 'is-active'
  };

  $.fn.header1 = function (options) {
    var opts = $.extend({}, $.fn.header.defaults, options);
    return this.each(function () {
      var $header = $(this);
      // do stuff with $header
    });
  }

})(jQuery);
