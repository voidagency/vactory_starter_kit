(function ($) {

  /**
   * Set active class on Views AJAX filter
   * on selected category
   */
  Drupal.behaviors.exposedfilter_buttons = {
    attach: function (context, settings) {
      $('.filter-tab a').on('click', function (e) {
        e.preventDefault();
        // Get ID of clicked item.
        var id = $(e.target).attr('id');
        // Get the select name.
        var selectName = $(e.target).attr('data-filter-name');

        // Set the new value in the SELECT element.
        var filter = $('[id^="views-exposed-form-annual-report"] select[name="' + selectName + '"]');
        filter.val(id);

        // Unset and then set the active class.
        $('.filter-tab a').removeClass('active');
        $(e.target).addClass('active');

        // Do it! Trigger the select box.
        filter.trigger('change');
        $('[id^="views-exposed-form-annual-report"] input.form-submit').trigger('click');
      });
    }
  };

  jQuery(document).ajaxComplete(function (event, xhr, settings) {
    if (typeof (settings.extraData) !== 'undefined' && typeof (settings.extraData.view_name) !== 'undefined' && settings.extraData.view_name === 'annual_report') {
      // Remove active class from all.
      $('.filter-tab a').removeClass('active');
      // Set active class to appropriate links.
      for (var key in parseQueryString(settings.data)) {
        if (key.startsWith('field_')) {
          $('[id^="' + parseQueryString(settings.data)[key] + '"][data-filter-name="' + key + '"]').addClass('active');
        }
      }
    }
  });

  /**
   *
   * @param queryString
   */
  function parseQueryString(queryString) {
    var params = {}, queries, temp, i, l;
    // Split into key/value pairs.
    queries = queryString.split("&");
    // Convert the array of strings into an object
    for (i = 0, l = queries.length; i < l; i++) {
      temp = queries[i].split('=');
      params[temp[0]] = temp[1];
    }
    return params;
  };

})(jQuery);
