(function ($, Drupal) {

  Drupal.behaviors.vactory_google_map_field_widget_renderer = {
    attach: function (context) {

      // code here to read fields and set maps accordingly on page load.
      googleMapFieldPreviews();

      $('.google-map-field-clear').bind('click', function (event) {
        event.preventDefault();
        var data_delta = $(this).attr('data-delta');
        $('input[data-name-delta="' + data_delta + '"]').prop('value', '').attr('value', '');
        $('input[data-lat-delta="' + data_delta + '"]').prop('value', '').attr('value', '');
        $('input[data-lon-delta="' + data_delta + '"]').prop('value', '').attr('value', '');
        $('input[data-zoom-delta="' + data_delta + '"]').prop('value', '').attr('value', 9);
        $('input[data-type-delta="' + data_delta + '"]').prop('value', '').attr('value', 'roadmap');
        googleMapFieldPreviews(data_delta);
      });

      $('.google-map-field-watch-change').change(function (event) {
        var data_delta = $(this).attr('data-lat-delta') || $(this).attr('data-lon-delta') || $(this).attr('data-zoom-delta') || $(this).attr('data-type-delta');
        googleMapFieldPreviews(data_delta);
      });

    }
  }

})(jQuery, Drupal);
