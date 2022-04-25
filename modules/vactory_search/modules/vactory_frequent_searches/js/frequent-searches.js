(function ($, Drupal) {

    'use strict';

    /**
     * Attaches the JS test behavior to weight div.
     */
    Drupal.behaviors.jsFrequentSearches = {
        attach: function (context, settings) {
            var items = $('.frequent_searches_select_items')
            $('.frequent-searches-select-items').on('change', function() {
                if (this.value == "all") {
                    items.each(function() {
                        $(this).prop('checked', true)
                    });
                }
                else if (this.value = "unselect") {
                    items.each(function() {
                        $(this).prop('checked', false)
                    });
                }
            });
        }
    };
})(jQuery, Drupal);