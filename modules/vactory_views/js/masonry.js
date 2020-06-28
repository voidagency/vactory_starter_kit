/**
 * @file
 * vactory_views behaviors.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Add masonry.
     */
    Drupal.behaviors.vactoryViewsMasonry = {
        attach: function (context, settings) {

            // Masonry
            if ($.fn.masonry) {
                $('.mansory-grid').masonry();
            }

            $('.vactory-masonry').each(function () {
                // Grab layout.
                var $grid = $(this);

                // Grab views masonry settings.
                var $settings_views = $.parseJSON($grid.attr('data-settings'));

                // Default settings.
                var $settings_defaults = {
                    // options
                    itemSelector: '.mansory-grid-item'
                };

                // Merge views settings with defaults.
                var $settings = $.extend({}, $settings_defaults, $settings_views);

                // Initialize.
                $grid.masonry($settings);
            });

        }
    };

}(jQuery, Drupal));
