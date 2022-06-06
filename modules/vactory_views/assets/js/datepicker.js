(function ($, Drupal) {

  'use strict';

  /**
   * Add masonry.
   */
  Drupal.behaviors.vactoryViewsDatePicker = {
    attach: function (context, settings) {
      $.fn.datepicker.defaults.language = settings.vactory_views.langcode;
    }
  };

}(jQuery, Drupal));
