(function ($, Drupal) {
	"use strict";

	Drupal.behaviors.vactory_icon = {
		attach: function (context, settings) {
			$('#vactory-icon-provider-select').change(function () {
				$('.vactory-icon-provider-trigger').click();
			});
		}
	}

})(jQuery, Drupal);
