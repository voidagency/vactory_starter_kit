(function ($, Drupal) {
  Drupal.behaviors.header_2 = {
    attach: function () {

      $('.main-menu > li.expanded > a').showMenu();

      $('.js__btn-show').cloneMenu();

    }
  };
})(jQuery, Drupal);
