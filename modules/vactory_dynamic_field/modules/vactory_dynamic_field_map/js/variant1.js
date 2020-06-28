//== Vactory Map.
//
//## Variant 1.
(function ($, Drupal, drupalSettings) {
  "use strict";

  function initialize() {
    var settings = drupalSettings.vactory_map.variant1;
    var center = settings.options.center.split(',');

    var myLatlng = new google.maps.LatLng(center[0], center[1]);
    var mapOptions = {
      zoom: settings.options.zoom,
      //style: settings.options.style,
      center: myLatlng
    };

    var map = new google.maps.Map(document.getElementById('vactory_map_variant1'), mapOptions);

    var infowindow = new google.maps.InfoWindow();
    var marker, i;

    for (i = 0; i < settings.locations.length; i++) {
      console.log(settings.locations[i].icon);
      var location = settings.locations[i].location.split(',');
      marker = new google.maps.Marker({
        position: new google.maps.LatLng(location[0], location[1]),
        map: map,
        title: settings.locations[i].title,
        icon: settings.locations[i].icon
      });

      google.maps.event.addListener(marker, 'click', (function (marker, i) {
        return function () {
          if (settings.locations[i].description.length > 0) {
            infowindow.setContent(settings.locations[i].description);
            infowindow.open(map, marker);
          }
        }
      })(marker, i));
    }

  }

  google.maps.event.addDomListener(window, 'load', initialize);

})(jQuery, Drupal, drupalSettings);
