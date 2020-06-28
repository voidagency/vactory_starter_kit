/**
 * @file
 * Vactory Locator.
 */

//plugin vactoryLocator

(function ($, Drupal) {
  var app = null;
  var element = null;

  $.fn.VactoryLocator = function (options) {


    /**
     * Global app container
     *
     * @name VactoryLocator
     * @type {VactoryLocator | jQuery}
     * @property {object | Google.map} map
     * @property {Object | InfoWindow} infoWindow
     * @property {object} data
     * @property {array} markers
     * @property {object} state
     */
    app = this;


    /**
     * App Settings.
     * Merge options and defaults, without modifying defaults
     */
    var settings = $.extend({}, $.fn.VactoryLocator.defaults, options);


    /**
     * Google map
     *  @type {null}
     */
    app.map = null;


    /**
     * Data fetched from the API
     *
     * Used to store all existing data to filter it
     * without using ajax calls for multiple times.
     * @type {object}
     */
    app.allData = {};

    /**
     * Data filtred
     * @type {object}
     */
    app.data = {};


    /**
     * List of Google markers
     * @type {array}
     */
    app.markers = [];


    /**
     * List of Default cluster
     * @type {array}
     */
    app.googleClusters = [];


    /**
     * Map Clusters
     */
    app.mapCluster = null;


    /**
     * InfoWindow
     * @type {Object | InfoWindow}
     */
    app.infoWindow = null;


    /**
     * State
     * @type {object}
     * ANY CHANGE TO THIS SHOULD BE ALSO APPLIED TO app.resetMap(). !!!
     */
    app.state = {
      currentMarker: null,
      query: '',
      searchMode: 'SHOW_ALL',
      filtredLocationsIndex: [],
      searchPagination: {
        currentPage: 1,
        totalPages: 0,
        totalResults: 0,
      },
    };


    /**
     * Events
     * @type {object}
     */
    app.events = {
      MAP_LOADED: 'map_loaded',
      DATA_LOADED: 'data_loaded',
      MARKERS_ADDED: 'markers_added',
      MARKER_CLICKED: 'marker_clicked',
      DOM_LIST_UPDATED: 'dom_list_updated',
      LOCATION_SELECTED: 'location_selected',
      DIRECTION_CLICKED: 'direction_clicked',
      GMAPS_CLICKED: 'gmaps_clicked',
      FILTER_REQUESTED: 'filter_requested'
    };


    /**
     * Dom Elements
     * @type {object}
     */
    app.dom = {
      searchInput: $('#vactory_locator_search_input'),
      locationsListContainer: $('#vactory_locator_locations_list_container'),
      locationsList: $('#vactory_locator_locations_list'),
      btnPaginationPrev: $('#btn_pagination_prev'),
      btnPaginationNext: $('#btn_pagination_next'),
      paginationStatus: $('#vactory_locator_pagination_status'),
      pagination: $('#vactory_locator_pagination'),
      clearSearch: $('#btn_clear_search'),
      gpsButton: $('.btn-gps'),
    };


    /**
     * Fire an event
     * @param {string} eventName
     * @param {object} data
     */
    app.triggerEvent = function (eventName, data) {
      if (data === undefined) {
        data = {};
      }
      $(document).trigger('vactory_locator.event.' + eventName, data);
    };


    /**
     * Event Listener
     * @param {string} eventName
     * @param {function} callback
     */
    app.listenEvent = function (eventName, callback) {
      $(document).on('vactory_locator.event.' + eventName, function (event, data) {
        callback(event, data);
      });
    };


    /**
     * Object User Position
     * Promise for current location
     */
     app.userPosition = {
      currentPosition : $.Deferred(),
      currentLatitude : 0,
      currentLonguitude : 0,
    };


    /**
     * Generate cluster svg
     * Default google cluster
     * @param {string} color
     */
    app.getGoogleClusterInlineSvg = function (color) {
      var encoded = window.btoa('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-100 -100 200 200"><defs><g id="a" transform="rotate(45)"><path d="M0 47A47 47 0 0 0 47 0L62 0A62 62 0 0 1 0 62Z" fill-opacity="0.7"/><path d="M0 67A67 67 0 0 0 67 0L81 0A81 81 0 0 1 0 81Z" fill-opacity="0.5"/><path d="M0 86A86 86 0 0 0 86 0L100 0A100 100 0 0 1 0 100Z" fill-opacity="0.3"/></g></defs><g fill="' + color + '"><circle r="42"/><use xlink:href="#a"/><g transform="rotate(120)"><use xlink:href="#a"/></g><g transform="rotate(240)"><use xlink:href="#a"/></g></g></svg>');

      return ('data:image/svg+xml;base64,' + encoded);
    };


    /**
     * Generate Colored cluster
     * Generate up to 5 object
     */
    app.googleClusters = [{
        width: 40,
        height: 40,
        url: app.getGoogleClusterInlineSvg('blue'),
        textColor: 'white',
        textSize: 12
      },
      {
        width: 50,
        height: 50,
        url: app.getGoogleClusterInlineSvg('violet'),
        textColor: 'white',
        textSize: 14
      },
      {
        width: 60,
        height: 60,
        url: app.getGoogleClusterInlineSvg('yellow'),
        textColor: 'white',
        textSize: 16
      }
      //up to 5
    ];


    app.getMarkerIcon = function () {
      return {
        url: settings.markerOptions.defaultMarkerUrl,
        size: new google.maps.Size(settings.markerOptions.width, settings.markerOptions.height),
        scaledSize: new google.maps.Size(settings.markerOptions.width, settings.markerOptions.height)
      };
    };


    app.showLocationList = function () {
      app.dom.locationsListContainer.show(); //removeClass('vactory_locator_close').addClass('vactory_locator_open');
    };


    app.hideLocationList = function () {
      app.dom.locationsListContainer.hide(); //removeClass('vactory_locator_open').addClass('vactory_locator_close');
    };


    app.updatePagination = function (total) {
      app.state.searchPagination.currentPage = 1;
      app.state.searchPagination.totalResults = total;
      app.state.searchPagination.totalPages = Math.ceil(total / settings.paginationPerPage);
    };


    app.getCurrentPage = function () {
      return app.state.searchPagination.currentPage;
    };


    /**
     * Load map
     * @param {element}
     */
    app.loadMap = function (element) {
      $.getScript('https://maps.googleapis.com/maps/api/js?region=MA&key=' + settings.googleApiKey, function () {
        app.map = new google.maps.Map(element, {
          center: {
            lat: 31,
            lng: -7.36133
          },
          zoom: 6,
          minZoom: 4,
          maxZoom: 17,
          mapTypeControl: false,
          fullscreenControl: false,
          streetViewControl: false,
          zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM
          },
          styles: settings.style
        });
        // Fire MAP_LOADED event
        app.triggerEvent(app.events.MAP_LOADED);
        // remove loading class from map element
        $(element).removeClass('loading');

      });
    };


    /**
     * Load data
     */
    app.loadData = function () {
      // Once the map was uploaded.
      app.listenEvent(app.events.MAP_LOADED, function () {
        app._loadData(settings.apiUrl);
      });
      // Once a filter is required.
      app.listenEvent(app.events.FILTER_REQUESTED, function () {
        app.resetMap();
        app.updatePagination(app.data.results.length);
        app.triggerEvent(app.events.DATA_LOADED);
      })
    };


    /**
     * Load data Stub.
     *
     * @param url
     * @private
     */
    app._loadData = function (url) {
      $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        cache: true,
        success: function (data) {
          app.data = data;
          // Store data in a variable to avoid multiple ajax calls.
          app.allData = Object.assign({}, data);
          app.updatePagination(data.results.length);
          app.triggerEvent(app.events.DATA_LOADED);
        },
        error: function (e) {
          console.error(e);
        }
      });
    };


    /**
     *
     * @param {object} element location element
     * @param {number} index the index of the element
     */
    app.addMarker = function (element, index) {

      // parse coordinates fetched to float and assign it to the new position
      // object
      var position = {
        lat: parseFloat(element.field_locator_info.lat),
        lng: parseFloat(element.field_locator_info.lon),
      };

      // create a new google LatLng object
      var latLng = new google.maps.LatLng(position);

      var marker = new google.maps.Marker({
        position: latLng,
        map: app.map,
        icon: app.getMarkerIcon(),
        cursor: 'pointer',
      });

      // Click
      google.maps.event.addListener(marker, 'click', (function () {
        // Emit signal.
        app.triggerEvent(app.events.MARKER_CLICKED, {
          marker: this,
          location_index: index
        });
      }));

      // ad marker to the list of markers
      app.markers.push(marker);
    };


    /**
     * Add markers to map
     */
    app.addMarkers = function () {
      app.listenEvent(app.events.DATA_LOADED, function () {

        var locations = app.data.results;

        locations.forEach(function (element, index) {
          app.addMarker(element, index);
        });

        app.triggerEvent(app.events.MARKERS_ADDED);

      });
    };


    /**
     * Add Clusters
     */
    app.addClusters = function () {
      app.listenEvent(app.events.MARKERS_ADDED, function () {

        app.mapCluster = new MarkerClusterer(app.map, app.markers, {
          styles: (settings.clusterStyles[0].url !== '') ? settings.clusterStyles : app.googleClusters,
          maxZoom: 14,
          ignoreHidden: false,
        });
      });
    };


    /**
     * Render pagination
     *
     */
    app.renderPagination = function () {
      var current_page = app.state.searchPagination.currentPage;
      var totalPages = app.state.searchPagination.totalPages;
      var totalResults = app.state.searchPagination.totalResults;
      var statusPagination = '';
      if (totalResults) {
        statusPagination = totalResults + Drupal.t(' Résultat(s). ') + current_page + Drupal.t(' sur ') + totalPages;
      } else {
        statusPagination = totalResults + Drupal.t(' Résultat.');
      }
      app.dom.paginationStatus.html(statusPagination);
    };


    /**
     * Todo : Add doc
     */
    app.renderLocationsList = function () {
      var locations_index = app.state.filtredLocationsIndex;
      var current_page = app.getCurrentPage();
      var per_page = settings.paginationPerPage;
      var mode = app.state.searchMode;
      var total_elements = 0;
      var isFiltred = false;

      switch (mode) {
        case 'SHOW_ALL':
          total_elements = app.data.results.length;
          isFiltred = false;
          break;
        case 'SHOW_FILTRED':
          total_elements = locations_index.length;
          isFiltred = true;
          break;
        default:
          total_elements = app.data.results.length;
          isFiltred = false;
      }

      // Clear the content of the list
      app.dom.locationsList.html('');

      var start = (current_page - 1) * per_page;
      var end = (current_page * per_page) >= total_elements ? total_elements : (current_page * per_page);

      for (var i = start; i < end; i++) {
        var index = isFiltred ? locations_index[i] : i;
        var listItem = $('<li></li>').addClass('list-group-item').attr('data-location-id', index);
        var search_list_element = $(app.data.results[index].search_list_element);
        listItem.append(search_list_element);


        (function (j) {
          listItem.on('click', function () {
            app.triggerEvent(app.events.LOCATION_SELECTED, j);
          });
        })(index);

        app.dom.locationsList.append(listItem);
      }

      // pagination status
      app.renderPagination();

      // Aucun résultat trouvé
      if (mode === 'SHOW_FILTRED' && total_elements === 0) {
        app.dom.locationsList.html(settings.noResultMessage);
        app.dom.locationsList.addClass('listeempty');
        app.dom.locationsList.parent().css("overflow", "hidden");
      } else {
        app.dom.locationsList.removeClass('listeempty');
        app.dom.locationsList.parent().css("overflow", "auto");
      }

      //moin de 9 resultet trouve
      if (total_elements < 9) {
        app.dom.pagination.css("display", "none");
      } else {
        app.dom.pagination.css("display", "flex");
      }

      app.triggerEvent(app.events.DOM_LIST_UPDATED);

    };

    /**
     * Search Function
     */
    app.search = function (query) {
      var filtredLocationsIndex = [];

      var name_normalize,
        adresse_line_1_normalize,
        adresse_line_2_normalize,
        city_normalize,
        name,
        adresse_line_1,
        adresse_line_2,
        city;

      var ua = window.navigator.userAgent;
      var msie = ua.indexOf("MSIE ");

      app.state.query = query;
      // check if query is blank, null or undefined
      if (!query || /^\s*$/.test(query)) {
        app.state.searchMode = 'SHOW_ALL';
        app.updatePagination(app.data.results.length);
        return filtredLocationsIndex;
      }

      function getnormalize(var_normalize) {
        return var_normalize.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
      }

      for (var i = 0; i < app.data.total_rows; i++) {

        name = app.data.results[i].name.toLowerCase();
        adresse_line_1 = app.data.results[i].field_locator_adress_address_line1.toLowerCase();
        adresse_line_2 = app.data.results[i].field_locator_adress_address_line2.toLowerCase();
        city = app.data.results[i].field_locator_adress_locality.toLowerCase();

        if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
          name_normalize = name;
          adresse_line_1_normalize = adresse_line_1;
          adresse_line_2_normalize = adresse_line_2;
          city_normalize = city;
        } else {
          name_normalize = getnormalize(name);
          adresse_line_1_normalize = getnormalize(adresse_line_1);
          adresse_line_2_normalize = getnormalize(adresse_line_2);
          city_normalize = getnormalize(city);
        }

        if (name_normalize.indexOf(query) >= 0 || adresse_line_1_normalize.indexOf(query) >= 0 || adresse_line_2_normalize.indexOf(query) >= 0 || city_normalize.indexOf(query) >= 0) {
          filtredLocationsIndex.push(i);
        }
      }

      app.state.searchMode = 'SHOW_FILTRED';
      app.updatePagination(filtredLocationsIndex.length);
      app.state.filtredLocationsIndex = filtredLocationsIndex;

    };


    /**
     * update InfoWindow
     * @param {integer} index
     * @returns {Object | InfoWindow}
     */
    app.getInfoWindow = function (index) {
      // We should keep just one InfoWindow Instance,
      // reassign it to different locations / markers upon map events
      if (app.infoWindow === null) {
        app.infoWindow = new google.maps.InfoWindow();
      }

      // update infoWindow Content
      var infoWindowContent = app.data.results[index].pin;
      app.infoWindow.setContent(infoWindowContent);
      return app.infoWindow;
    };


    /**
     * Reset Map Direction
     */
    app.resetDirection = function () {
      if (typeof directionsDisplay != "undefined") {
        directionsDisplay.setMap(null);
        directionsDisplay.setPanel(null);
      }
    };


    /**
     * Reset map state.
     */
    app.resetMap = function () {

      if (app.mapCluster != null) {
        app.mapCluster.clearMarkers();
      }

      app.state = {
        currentMarker: null,
        query: '',
        searchMode: 'SHOW_ALL',
        filtredLocationsIndex: [],
        searchPagination: {
          currentPage: 1,
          totalPages: 0,
          totalResults: 0
        }
      };

      // Clear markers.
      app.markers = [];
      app.infoWindow = null;
      app.mapCluster = null;
      app.resetDirection();
    };

    /**
     * set psotion for
     * the global app.userPosition
     */
    app.setPosition = function (position) {
      app.userPosition.currentLatitude = position.coords.latitude;
      app.userPosition.currentLongitude = position.coords.longitude;
      app.userPosition.currentPosition.resolve();
    };


    /**
     * Show Modal Error
     * if any error occured
     * when getting user position
     */
    app.error = function (err) {
      //alert(err.message);
      $('#ModalGps').modal('show');
      $('.modal-body').html("<p>" + err.message + "</p>");
    };


    /**
     * get the current position
     * of the user and add marker to the map
     */
    app.getLocalisation = function () {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(app.setPosition, app.error);

        $.when(app.userPosition.currentPosition).done(function() {
          var localisation = {
            lat: app.userPosition.currentLatitude,
            lng: app.userPosition.currentLongitude
          };

          // create a new google LatLng object
          var latLng = new google.maps.LatLng(localisation);
          var marker = new google.maps.Marker({
            position: latLng,
            map: app.map,
            icon: settings.geolocalisationMarker.url,
            // animation: google.maps.Animation.DROP,
            cursor: "pointer"
          });
          }).promise();

      } else {
        //alert("Geolocation is not supported by this browser.");
        $("#ModalGps").modal("show");
        $(".modal-body").html(
          "<p> Geolocation is not supported by this browser. </p>"
        );
      }
    };


    /**
     * open the location
     * in google maps web site
     */
    app.openInGmaps = function (latitude, longitude) {
      window.open("http://maps.google.com/maps?q=" + latitude + "," + longitude + "&amp;ll=");
    };


    /**
     * Calculate Road & distance
     * and show the direction in the map
     */
    app.calculateRoute = function (latitude_end_pos, longitude_end_pos) {

      function initRoute() {
        current_pos = new google.maps.LatLng(
          app.userPosition.currentLatitude,
          app.userPosition.currentLongitude
        );
        end_pos = new google.maps.LatLng(latitude_end_pos, longitude_end_pos);

        var request = {
          origin: current_pos,
          destination: end_pos,
          travelMode: google.maps.TravelMode.DRIVING
        };

        var directionsService = new google.maps.DirectionsService();
        var directionsDisplay = new google.maps.DirectionsRenderer();

        directionsService.route(request, function (result, status) {
          if (status == google.maps.DirectionsStatus.OK) {
            var data = result.routes[0].legs[0];
            var debPoint = data.start_location;
            var endPoint = data.end_location;
            var distance_direction = result.routes[0].legs[0].distance.value;
            document.getElementById('distance').innerHTML = "Distance :";
            document.getElementById('distance').innerHTML += distance_direction < 1000 ? distance_direction + " m" : (distance_direction / 1000) + " km";
            var polyLineOptions = {
              strokeColor: "#2196F3",
              zIndex: 380,
              strokeWeight: 5
            };
            var options = {};
            options.map = app.map;
            options.directions = result;
            options.suppressMarkers = true;
            options.polylineOptions = polyLineOptions;
            directionsDisplay.setOptions(options);
          }
        });
      }

      /**
      * check if the google
      * direction service is activated
      */
      if (settings.googleDirectionService) {
        app.getLocalisation();
        app.resetDirection();

        // wait until the async getcurrentPosition function done
        return $.when(app.userPosition.currentPosition).done(function () {
          initRoute();
        }).promise();

      } else {
        $("#ModalGps").modal("show");
        $(".modal-body").html(
          "<p> Google Direction Service is not activated. </p>"
        );
        return;
      }

    };


    /**
     * Events handler
     */
    app.eventsHandler = function () {

      // on get direction button clicked
      app.listenEvent(app.events.DIRECTION_CLICKED, function (event, data) {
        $(document).on('click', '.itineraire-btn', function (e) {
          e.preventDefault();
          var lat_end = $(this).data('lat');
          var lon_end = $(this).data('lon');
          app.calculateRoute(lat_end, lon_end);
        });
      });

      // on show direction in google maps button clicked
      app.listenEvent(app.events.GMAPS_CLICKED, function (event, data) {
        $(document).on('click', '.gmaps-btn', function (e) {
          e.preventDefault();
          var lat = $(this).data('lat');
          var lon = $(this).data('lon');
          app.openInGmaps(lat, lon);
        });
      });

      // On marker clicked
      app.listenEvent(app.events.MARKER_CLICKED, function (event, data) {

        // 1. center map to selected position (marker)
        var centerPosition = data.marker.getPosition();
        // app.map.setCenter(centerPosition);
        app.map.setCenter({
          lat: (centerPosition.lat() + 0.001),
          lng: centerPosition.lng()
        });

        // 2. zoom map
        app.map.setZoom(17);

        // 3. show infoWindow
        var infoWindow = app.getInfoWindow(data.location_index);
        infoWindow.open(app.map, data.marker);
        app.hideLocationList();
        app.dom.clearSearch.click();
        app.triggerEvent(app.events.DIRECTION_CLICKED);
        app.triggerEvent(app.events.GMAPS_CLICKED);
      });


      app.listenEvent(app.events.DATA_LOADED, function () {

        // render the location list Element
        app.renderLocationsList();
      });


      app.listenEvent(app.events.LOCATION_SELECTED, function (event, data) {
        // 1. center map to selected position (marker)
        var centerPosition = app.markers[data].getPosition();
        // app.map.setCenter(centerPosition);
        app.map.setCenter({
          lat: (centerPosition.lat() + 0.001),
          lng: centerPosition.lng()
        });
        // 2. zoom map
        app.map.setZoom(17);

        // 3. show infoWindow
        var infoWindow = app.getInfoWindow(data);
        infoWindow.open(app.map, app.markers[data]);
        app.hideLocationList();
        app.dom.clearSearch.click();
        app.triggerEvent(app.events.DIRECTION_CLICKED);
        app.triggerEvent(app.events.GMAPS_CLICKED);
      });


      app.listenEvent(app.events.MAP_LOADED, function () {

        google.maps.event.addListener(app.map, 'click', function () {
          app.hideLocationList();
        });
        google.maps.event.addListener(app.map, 'drag', function () {
          app.hideLocationList();
        });

      });


      // button geolocalisation click {gps}
      app.dom.gpsButton.click(function (e) {
        e.preventDefault();
        app.hideLocationList();

        app.getLocalisation();

      $.when(app.userPosition.currentPosition).done(function () {
        app.map.setCenter({
          lat: app.userPosition.currentLatitude,
          lng: app.userPosition.currentLongitude
        });
        app.map.setZoom(17);
      }).promise();

      });


      // on search
      app.dom.searchInput.on('focus', function () {
        app.showLocationList();
      });
      app.dom.searchInput.on('keypress', function () {
        app.dom.clearSearch.show();
      });
      app.dom.searchInput.on('keyup', function () {
        var query = $(this).val().toLowerCase();

        // get the list of filtred locations by query
        app.search(query);
        app.renderLocationsList();
      });
      app.dom.clearSearch.on('click', function () {
        app.dom.searchInput.val("");
        app.state.query = "";
        app.updatePagination(app.data.results.length);
        app.state.searchPagination.currentPage = 1;
        app.state.searchMode = 'SHOW_ALL';
        app.renderLocationsList();
        app.dom.clearSearch.hide();
        app.dom.locationsListContainer.hide();
      });

      app.dom.btnPaginationPrev.on('click', function (e) {
        e.preventDefault();
        var current_page = app.state.searchPagination.currentPage;
        var totalPages = app.state.searchPagination.totalPages;
        var prev_page = current_page;
        if (current_page > 1) {
          prev_page = current_page - 1;
          app.state.searchMode = app.state.query ? 'SHOW_FILTRED' : 'SHOW_ALL';
          app.state.searchPagination.currentPage = prev_page;
          app.renderLocationsList();
        }

      });
      app.dom.btnPaginationNext.on('click', function (e) {
        e.preventDefault();
        var current_page = app.state.searchPagination.currentPage;
        var totalPages = app.state.searchPagination.totalPages;
        var next_page = current_page;
        if (current_page < totalPages) {
          next_page = current_page + 1;
          app.state.searchMode = app.state.query ? 'SHOW_FILTRED' : 'SHOW_ALL';
          app.state.searchPagination.currentPage = next_page;
          app.renderLocationsList();
        }

      });

    };

    // Filter by categories;
    $('.locator-category').on('click', function (e) {
      e.preventDefault();
      var tid = $(this).data('term-id');
      if (tid != undefined && tid != "") {
        app.data = Object.assign({}, app.allData);
        if (tid !== "all") {
          app.data.results = app.data.results.filter(function(el) {
            return el.category_id === tid;
          });
          app.data.total_rows = app.data.results.length;
        }
        app.triggerEvent(app.events.FILTER_REQUESTED);
      }
    });


    return app.each(function () {

      // selected DOM Element
      element = this;

      // Load Map
      app.loadMap(element);

      // Load data
      app.loadData();

      // Add markers
      app.addMarkers();

      // Add marker clusterer
      app.addClusters();

      // listen to events
      app.eventsHandler();


    });

  };


  $.fn.VactoryLocator.defaults = {
    googleApiKey: '',
    apiUrl: '',
    markerOptions: [],
    clusterStyles: [],
    styles: [],
    noResultMessage: '',
    paginationPerPage: 20
  };

})(jQuery, Drupal);


(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.vactory_locator = {

    attach: function (context, settings) {
      var options = {
        googleApiKey: drupalSettings.vactory_locator.map_key,
        apiUrl: drupalSettings.vactory_locator.url,
        viewApiUrl: drupalSettings.vactory_locator.v_url,
        markerIconUrl: drupalSettings.vactory_locator.url_marker,
        clusterIconUrl: drupalSettings.vactory_locator.url_cluster,
        noResultMessage: drupalSettings.vactory_locator.no_result_msg || 'Aucun résultat trouvé',
        googleDirectionService: (drupalSettings.vactory_locator.use_geolocation != 0) ? true : false,
        geolocalisationMarker: {
          url: (drupalSettings.vactory_locator.url_geolocation_marker !== '') ? drupalSettings.vactory_locator.url_geolocation_marker : '', // '/themes/vactory/assets/img/geolocalisation.png'
        },
        markerOptions: {
          defaultMarkerUrl: (drupalSettings.vactory_locator.url_marker !== '') ? drupalSettings.vactory_locator.url_marker : '/themes/vactory/assets/img/marker.png',
          width: 44,
          height: 53,
        },
        clusterStyles: [{
          url: (drupalSettings.vactory_locator.url_cluster !== '') ? drupalSettings.vactory_locator.url_cluster : '', // '/themes/vactory/assets/img/cluster.png'
          height: 53,
          width: 44,
          textColor: "#671F5B",
          textSize: 16,
          cssClass: "custom-pinnn"
        }],
        style: (drupalSettings.vactory_locator.map_style !== '') ? JSON.parse( drupalSettings.vactory_locator.map_style ) : '',
      };

      $('#vactory_locator_map').VactoryLocator(options);
    }

  };
})(jQuery, Drupal, drupalSettings);
