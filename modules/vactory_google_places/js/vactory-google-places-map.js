/**
 * @file
 * Contains the definition of the behaviour placeAutocomplete.
 */

(function ($) {
	'use strict';

	/**
	 * Attaches the placeAutocomplete Behaviour.
	 */
	Drupal.behaviors.vactoryGooglePlaceMap = {
		attach: function (context, settings) {
			if (typeof drupalSettings.map_view != 'undefined') {
				initMap();
			}
		}
	};
})(jQuery);

/**
 *  Prepare Map with Dynamic variables.
 */
function initMap() {
	var zoom_level = drupalSettings.map_view.autocomplete.zoom_level;
	var map_type = drupalSettings.map_view.autocomplete.map_type;
	var map_width = drupalSettings.map_view.autocomplete.map_width;
	var map_height = drupalSettings.map_view.autocomplete.map_height;
	var controls = drupalSettings.map_view.autocomplete.controls;
	var infowindow = drupalSettings.map_view.autocomplete.infowindow;
	var content = drupalSettings.map_view.autocomplete.content;
	var latitude = drupalSettings.map_view.autocomplete.latitude;
	var longitude = drupalSettings.map_view.autocomplete.longitude;
	var drag = drupalSettings.map_view.autocomplete.drag;

	jQuery("#map").css({"width":map_width, "height":map_height});
	var latlng = new google.maps.LatLng(latitude, longitude);

	var map = new google.maps.Map(document.getElementById('map'), {
		zoom: parseInt(zoom_level),
		center: latlng,
		mapTypeId: map_type,
		disableDefaultUI: !controls,
		draggable: !drag,
	});

	var marker = new google.maps.Marker({
		position: latlng,
		map: map,
	});

	if (infowindow) {
		infowindow = new google.maps.InfoWindow({
			content: content
		});

		marker.addListener('click', function() {
			infowindow.open(map, marker);
		});
	}
}
