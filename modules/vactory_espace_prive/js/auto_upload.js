
(function ($, Drupal) {
  "use strict";
    $(document).ready(function () {
      $(".btn-edit-picto").click(function (e) {
        // var value = $(".profile-picto input").val();
        if ($('.profile-picto input[type="file"]').length > 0) {
          $('.profile-picto input[type="file"]').click();
        }
        else {
          $('.profile-picto input[type="submit"]').mousedown();
        }
        $(document).ajaxComplete(function (event, xhr, settings) {
          if (
            settings.extraData !== undefined &&
            settings.extraData._triggering_element_name !== undefined &&
            settings.extraData._triggering_element_name.match(/^user_picture_(.)*_remove_button$/)
          ) {
            $('.profile-picto input[type="file"]').click();
          }
        });
        jQuery("#uploaded_img").attr( "src", "/themes/vactory/assets/img/user-avatar.svg");
      });
    });

})(jQuery, Drupal);
