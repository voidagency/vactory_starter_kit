(function ($, Drupal, drupalSettings) {
    $(document).ready(function () {
        var element = ("#v8-modal");
        var using_cookie =  parseInt(drupalSettings.cookie_pop);
        if (using_cookie !== 0) {
            if (typeof $.cookie('popupdf') === "undefined") {
                $.cookie('popupdf', using_cookie, { expires: 7, path: drupalSettings.current_path});
            } else {
                return;
            }
        }
        $(element).on('shown.bs.modal', function(e) {
            // keep in mind this only works as long as Bootstrap only supports 1 modal at a time, which is the case in Bootstrap 3 so far...
            let backDrop = $('.modal-backdrop');
            $(backDrop).removeClass('modal-backdrop');
        });
       $(element).modal('show');

    });

})(jQuery, Drupal, drupalSettings);

