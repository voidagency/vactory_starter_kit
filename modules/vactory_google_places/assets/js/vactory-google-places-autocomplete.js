/**
 * @file
 * Contains the definition of the behaviour placeAutocomplete.
 */

(function ($) {
	'use strict';

	/**
	 *  Location Search by using Google Place Autocomplete.
	 */
	function locationInitialize() {
		var options = {};
		var inputs = $('.vactory-google-places');
		if (typeof drupalSettings.place != 'undefined') {
			var code = drupalSettings.place.autocomplete;
			if(code.length > 0) {
				options = {
					componentRestrictions: {country: code} //Country Code
				};
			}
		}
		var autocomplete = null;
		inputs.each(function (i) {
			autocomplete = new google.maps.places.Autocomplete(this, options);
		});
	}

	/**
	 * Attaches the placeAutocomplete Behaviour.
	 */
	Drupal.behaviors.vactoryGooglePlaceAutocomplete = {
		attach: function (context, settings) {
			google.maps.event.addDomListener(window, 'load', locationInitialize);
		}
	};
})(jQuery);
