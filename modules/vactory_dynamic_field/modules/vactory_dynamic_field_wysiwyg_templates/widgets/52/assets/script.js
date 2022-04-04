(function ($, Drupal) {
  "use strict";

  $(function () {

    let initialWindowScroll = $(window).scrollTop();

    console.log(initialWindowScroll);

    // let mobileTrigger = $('.toolbox__trigger-mobile');

    let toolboxWrapper = $('.toolbox__wrapper');

    toolboxWrapper.addClass('show-all');

    if (initialWindowScroll > 80) {
      setTimeout(function () {
        toolboxWrapper.removeClass('show-all');
      }, 1500);
      clearTimeout();
    }

    $(window).on('scroll', function(event) {
      let scrollTop = $(window).scrollTop();

      if (scrollTop > 100) {
        toolboxWrapper.removeClass('show-all');
      } else {
        toolboxWrapper.addClass('show-all');
      }

    });

    // mobileTrigger.on('click', function(e) {
    //   toolboxWrapper.toggleClass('is-open');
    // });

  });

})(jQuery, Drupal);
