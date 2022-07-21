(function ($, Drupal, drupalSettings) {

  /**
   * Set active class on Views AJAX filter
   * on selected category
   */

  Drupal.behaviors.exposedfilter_buttons = {
    attach: function (context, settings) {
      $('.filter-tab button', context).once('filterHome').on('click', function (e) {
        e.preventDefault();
        var el = $(e.target).prop('tagName') === 'SPAN' ? $(e.target).parent('button.btn-filter') : $(e.target);
        // Get ID of clicked item.
        var id = el.attr('id');
        // Get the select name.
        var selectName = el.data('filter-name');
        // Set the new value in the SELECT element.
        var formId = drupalSettings.form_id;
        var filter = $('[id^="'+ formId +'"] select[name="' + selectName + '"]');
        filter.val(id);

        if (id === 'all') {
          // Set the active class to all content types.
          $('.filter-tab button').addClass('active');
        }
        else {
          // Unset and then set the active class to selected type.
          $('.filter-tab button').removeClass('active');
          el.addClass('active');
        }


        // Do it! Trigger the select box.
        filter.trigger('change');
        $('[id^="'+ formId +'"] input.form-submit').trigger('click');
      });
    }
  };

  $(document).ready(function () {
    // Add JS read more/less link.
    $readMoreJS.init({
      target: '.more-less-link',
      numOfWords: 80,
      toggle: true,
      moreLink: Drupal.t('Afficher plus'),
      lessLink: Drupal.t('Afficher moins')
    });
  });

  $(document).ajaxComplete(function (event, xhr, settings) {
    if (typeof (settings.extraData) !== 'undefined' && typeof (settings.extraData.view_name) !== 'undefined' && settings.extraData.view_name === 'vactory_social_network') {
      // Remove active class from all.

      var objKey = Object.keys(parseQueryString(settings.data));
      if(!jQuery.inArray("type%5B%5D", objKey)) {
        $('.filter-tab button').removeClass('active btn-active');
      }
      // Set active class to appropriate links.
      for (var key in parseQueryString(settings.data)) {
        if (key.startsWith('type')) {
          // @TODO set the key dynamically.
          var vkey = decodeURI(key);
          var el = $('[id^="' + parseQueryString(settings.data)[key] + '"][data-filter-name="' + vkey + '"]');
          el.addClass('active btn-active');
        }
      }
      // Add JS read more/less link.
      $readMoreJS.init({
        target: '.more-less-link',
        numOfWords: 80,
        toggle: true,
        moreLink: Drupal.t('Afficher plus'),
        lessLink: Drupal.t('Afficher moins')
      });
    }
  });

  /**
   *
   * @param queryString
   */
  function parseQueryString(queryString) {
    var params = {},
      queries, temp, i, l;
    // Split into key/value pairs.
    queries = queryString.split("&");
    // Convert the array of strings into an object
    for (i = 0, l = queries.length; i < l; i++) {
      temp = queries[i].split('=');
      params[temp[0]] = temp[1];
    }
    return params;
  }

})(jQuery, Drupal, drupalSettings);
