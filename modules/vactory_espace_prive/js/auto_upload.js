
(function ($, Drupal) {
  "use strict";
    $(document).ready(function () {
      $(".btn-edit-picto").click(function (e) {
        // var value = $(".profile-picto input").val();
         $(".profile-picto input").mousedown();
         $(".profile-picto input").ajaxComplete(function () {
           $(".profile-picto input").click();
         });
          jQuery("#uploaded_img").attr( "src"," /themes/vactory/assets/img/user-avatar.svg");
          setTimeout(() => {
           $(".profile-picto input").click();
          }, 2000);
      });
    });

})(jQuery, Drupal);
