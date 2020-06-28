var vactory_google_map_field_map;

(function ($, Drupal) {

  Drupal.behaviors.vactory_google_map_field_renderer = {
    attach: function (context) {

      $('.google-map-field .map-container').once('.google-map-field-processed').each(function (index, item) {
        // Get the settings for the map from the Drupal.settings object.
        var lat = $(this).attr('data-lat');
        var lon = $(this).attr('data-lon');
        var zoom = parseInt($(this).attr('data-zoom'));
        var type = $(this).attr('data-type');
        var show_marker = $(this).attr('data-marker-show') === "true";
        var show_controls = $(this).attr('data-controls-show') === "true";
        var info_window = $(this).attr('data-infowindow') === "true";

        // Create the map coords and map options.
        var latlng = new google.maps.LatLng(lat, lon);
        var mapOptions = {
          zoom: zoom,
          center: latlng,
          streetViewControl: false,
          mapTypeId: type,
          disableDefaultUI: show_controls ? false : true,
        };

        var vactory_google_map_field_map = new google.maps.Map(this, mapOptions);

        google.maps.event.addDomListener(window, 'resize', function () {
          var center = vactory_google_map_field_map.getCenter();
          google.maps.event.trigger(vactory_google_map_field_map, "resize");
          vactory_google_map_field_map.setCenter(center);
        });

        // Drop a marker at the specified position.
        var marker = new google.maps.Marker({
          position: latlng,
          optimized: false,
          visible: show_marker,
          map: vactory_google_map_field_map
        });

        if (info_window) {
          var info_markup = $(this).parent().find('.map-infowindow').html();
          var infowindow = new google.maps.InfoWindow({
            content: info_markup
          });

          marker.addListener('click', function () {
            infowindow.open(vactory_google_map_field_map, marker);
          });
        }

      });

    }
  }

})(jQuery, Drupal);
