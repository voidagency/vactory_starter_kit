(function ($) {

  /**
   * Set active class on Views AJAX filter
   * on selected category
   */
  Drupal.behaviors.exposedfilter_buttons = {
    attach: function (context, settings) {
      $('.filter-tab').on('click', function (e) {
        e.preventDefault();
        // Get ID of clicked item.
        var selectID = jQuery(e.target).attr('data-filter-id');
        // Set the new value in the SELECT element.
        var filter = $('select[name = "field_medium_year_target_id"]');
        filter.val(selectID);
        // Unset and then set the active class.
        $('.filter-tab button').removeClass('active');
        $(e.target).addClass('active');
        // Do it! Trigger the select box.
        filter.trigger('change');
        $('input[id = "edit-submit-mediatheque"]').trigger('click');
      });
    }
  }

})(jQuery);

