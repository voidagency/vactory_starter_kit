//== Vactory App Banners.

(function ($, Drupal, drupalSettings) {
  "use strict";

  var settings = drupalSettings.appbanners;

  $.smartbanner({
    title: settings.title, // What the title of the app should be in the banner (defaults to <title>)
    author: settings.author, // What the author of the app should be in the banner (defaults to <meta name="author"> or hostname)
    price: settings.price, // Price of the app
    appStoreLanguage: settings.app_store_language, // Language code for App Store
    inAppStore: Drupal.t(settings.in_app_store), // Text of price for iOS
    inGooglePlay: Drupal.t(settings.in_google_play), // Text of price for Android
    url: settings.url, // The URL for the button. Keep null if you want the button to link to the app store.
    button: Drupal.t(settings.button), // Text for the install button
    scale: settings.scale, // Scale based on viewport size (set to 1 to disable)
    speedIn: settings.speed_in, // Show animation speed of the banner
    speedOut: settings.speed_out, // Close animation speed of the banner
    daysHidden: settings.days_hidden, // Duration to hide the banner after being closed (0 = always show banner)
    daysReminder: settings.days_reminder, // Duration to hide the banner after "VIEW" is clicked *separate from when the close button is clicked* (0 = always show banner)
    hideOnInstall: settings.hide_on_install, // Hide the banner after "VIEW" is clicked.
    layer: settings.layer, // Display as overlay layer or slide down the page
    iOSUniversalApp: settings.iOS_universal_app, // If the iOS App is a universal app for both iPad and iPhone, display Smart Banner to iPad users, too.
    appendToSelector: settings.append_to_selector, //Append the banner to a specific selector
    force: null, // Choose 'ios', 'android' or 'windows'. Don't do a browser check, just always show this banner
    GooglePlayParams: null, // Aditional parameters for the market
    icon: null, // The URL of the icon (defaults to <meta name="apple-touch-icon">)
    iconGloss: null, // Force gloss effect for iOS even for precomposed
    inAmazonAppStore: 'In the Amazon Appstore',
    inWindowsStore: 'In the Windows Store', // Text of price for Windows
    onInstall: function() {
    },
    onClose: function() {
    }
  })

})(jQuery, Drupal, drupalSettings);
