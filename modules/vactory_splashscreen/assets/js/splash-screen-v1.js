/**
 * splash-screen-v1.js
 */

(function ($, drupalSettings) {
  "use strict";

  var delays = drupalSettings.splashScreen.delays,
    howMany = drupalSettings.splashScreen.how_many,
    cookieName = drupalSettings.splashScreen.cookie.name,
    cookieIsSecure = drupalSettings.splashScreen.cookie.has_https,
    isAnonymous = drupalSettings.splashScreen.user.is_anonymous;


  // Only for Anonymous.
  if (isAnonymous) {
    // Check if Cookies.js is available
    if (typeof Cookies === 'undefined') {
      console.warn('Could not create a cookie for splash screen module. js-cookie library not loaded.');
      delays = 0;
    }
    else {
      var splash_cookie_how_many = Cookies.get(cookieName);

      // Default value.
      splash_cookie_how_many = (typeof splash_cookie_how_many === 'undefined') ? 1 : parseInt(splash_cookie_how_many);

      // We reached the limit.
      // No delay loader + Splash not showing
      if (splash_cookie_how_many > howMany) {
        delays = 0;
      }
      else {
        $('.splash-screen.variant1').show();
        setTimeout(function(){
          // disable scrolling.
          window.addEventListener('scroll', noscroll);
          $('.splash-screen.variant1').addClass('vf-show');
        },50);

        // Increase display time.
        var cookie_value = splash_cookie_how_many > 0 ? splash_cookie_how_many + 1 : 1;
        // Save for 365 days.
        Cookies.set(cookieName, cookie_value, {
          expires: 365,
          path: '/',
          secure: cookieIsSecure
        });
      }
    }
  }
  else {
    // Logged in user have no delays.
    delays = 0;
  }

  function noscroll() {
    window.scrollTo(0, 0);
  }
  function closeSplash() {
    window.removeEventListener('scroll', noscroll);
    $('.splash-screen.variant1').removeClass('vf-show').fadeOut();
  }

  $('.splash-screen.variant1 .close').on('click', function(e) {
    e.preventDefault();
    closeSplash();
  });

  // Page fully loaded.
  $(window).bind("load", function () {
    setTimeout(function () {
      closeSplash();
    }, delays * 1000);
  });

})(jQuery, drupalSettings);
