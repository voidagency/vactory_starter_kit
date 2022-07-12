
(function (Drupal) {
  "use strict";

  /**
   * Add new custom command.
   */
  Drupal.AjaxCommands.prototype.triggerManagedFileUploadComplete = function (
    ajax,
    response,
    status
  ) {
      jQuery("#uploaded_img").attr("src", response.file_path);

  };
})(Drupal);
