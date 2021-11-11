/**
 * push-notifications-v1.js
 */

(function ($, drupalSettings) {
  "use strict";

  var delays = drupalSettings.pushNotification.delays,
    howMany = drupalSettings.pushNotification.how_many,
    cookieName = drupalSettings.pushNotification.cookie.name,
    cookieIsSecure = drupalSettings.pushNotification.cookie.has_https;

  // Can we create cookies ?
  if (!$.isFunction($.cookie)) {
    console.warn('Could not create a cookie for push notifications module. $.cookie still in use ?');
    delays = 0;
  }
  else {
    var notification_cookie_how_many = $.cookie(cookieName);

    // Default value.
    notification_cookie_how_many = (typeof notification_cookie_how_many === 'undefined') ? 0 : parseInt(notification_cookie_how_many);
    if (howMany === 0) {
      $('.push-notifications.variant1').show();
    }
    else {
    // We reached the limit.
    // No delay loader + notification not showing
      $('.push-notifications.variant1').show();
      // Increase display time.
      var cookie_value = notification_cookie_how_many > 0 ? notification_cookie_how_many++ : 1;
      // Save for 365 days.
      $.cookie(cookieName, cookie_value, {
        expires: 365,
        path: '/',
        secure: cookieIsSecure
      });
    }
  }
  function closeNotification() {
    $('.push-notifications.variant1').removeClass('vf-show').fadeOut();
  }

  $('.push-notifications.variant1 .close').on('click', function(e) {
    e.preventDefault();
    closeNotification();
  });

  // Page fully loaded.
  $(window).bind("load", function () {
    if (delays !== 0) {
      setTimeout(function () {
        closeNotification();
      }, delays * 1000);
    }
  });

})(jQuery, drupalSettings);
