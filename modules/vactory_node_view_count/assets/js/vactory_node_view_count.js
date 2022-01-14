/**
 * @file
 * Vactory node view count behaviors.
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * Behavior description.
     */
    Drupal.behaviors.vactoryNodeViewCount = {
        attach: function (context, settings) {
            // Ensure executing ajax request once.
            if (context !== document) {
                return;
            }
            $.ajax({
                type: 'PUT',
                cache: false,
                url: drupalSettings.vactory_node_view_count.url,
            });
        }
    };

} (jQuery, Drupal, drupalSettings));
