(function ($, drupalSettings) {
  "use strict";
  $(document).ready(function () {
    var fb_validation_token;
    var token_page_view = drupalSettings['fb_validation_token']['PageView'];
    var event_id_page_view = drupalSettings['fb_validation_event_id']['PageView'];
    var token_complete_registration = drupalSettings['fb_validation_token']['CompleteRegistration'];
    var event_id_complete_registration = drupalSettings['fb_validation_event_id']['CompleteRegistration'];
    if (token_complete_registration != null) {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        'fbtrace_id' : token_complete_registration,
        'event_id_cr' : event_id_complete_registration,
      });
    }
    if (token_page_view != null) {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        'fbtrace_id' : token_page_view,
        'event_id_pv' : event_id_page_view,
      });
    }
  });
})(jQuery, drupalSettings)
