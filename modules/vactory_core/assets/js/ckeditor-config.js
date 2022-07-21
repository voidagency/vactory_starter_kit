(function ($, Drupal) {
    Drupal.behaviors.dcustomCKEditorConfig = {
        attach: function (context, settings) {

            if(window.CKEDITOR && typeof CKEDITOR !== "undefined"){
                CKEDITOR.on('instanceCreated', function (ev) {
                    CKEDITOR.dtd.$removeEmpty.span = 0;
                    CKEDITOR.dtd.$removeEmpty.i = 0;
                    CKEDITOR.dtd.$removeEmpty.a = 0;
                });
            }
        }
    }
})(jQuery, Drupal);
