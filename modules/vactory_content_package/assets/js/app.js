/**
 * @file
 * Javascript file for vactory_content_package
 */

(function ($, Drupal, drupalSettings) {
	Drupal.behaviors.vactory_content_package = {
		attach: function attach() {
			$(document).ajaxComplete(function(event, xhr, settings) {
				if (settings.extraData !== undefined && settings.extraData._triggering_element_name === 'template') {
					$(".df-console-modal-opener").trigger('click')
				}
			});
			$(".select-template-item").click(function (e) {
				$(".select-template-item").removeClass('df-selected-template')
				$(this).addClass('df-selected-template')
			})
			if (drupalSettings.vactory_content_package !== undefined && drupalSettings.vactory_content_package.template_json !== undefined) {
				var options = {
					editable: false,
				}
				var editor = new JsonEditor('#json-display', JSON.parse(drupalSettings.vactory_content_package.template_json), options)
			}
		}
	}
})(jQuery, Drupal, drupalSettings);
