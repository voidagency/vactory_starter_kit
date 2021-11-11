(function ($, Drupal) {
  Drupal.behaviors.vactory_user_guide = {
    attach: function (context, settings) {
      var toursSettings = settings.vactory_user_guide.tours_settings;
      if (toursSettings !== undefined) {
        // Build tours process.
        this.buildTours(toursSettings);
      }
    },

    buildTours: function (toursSettings) {
      // Check either the steps selectors exist or not, delete step with invalid selector.
      toursSettings.steps = $.grep(toursSettings.steps, function (step) {
        return step.element === '' ? true : document.querySelector(step.element);
      });
      // Loop through steps and use selector if exist.
      toursSettings.steps.forEach(function (step) {
        if (step.element !== '') {
          step.element = document.querySelector(step.element);
        }
        else {
          // Empty selector means displaying an elementless introduction.
          delete step.element;
        }
        return step;
      });
      // Step number to start with.
      var startWithStep = toursSettings.startStepNumber;
      // How much time tour guide should be displayed.
      var tourDisplayCounter = toursSettings.tourDisplayCounter;
      var blockMachineName = toursSettings.blockMachineName;
      var useHints = toursSettings.useHints;
      // Clean tour options.
      delete toursSettings.startStepNumber;
      delete toursSettings.tourDisplayCounter;
      delete toursSettings.blockMachineName;
      delete toursSettings.useHints;
      if (!$.cookie('ugToursCounter_' + blockMachineName)) {
        // Initialize tour counter cookie variable if not exist.
        $.cookie('ugToursCounter_' + blockMachineName, tourDisplayCounter);
      }
      if (parseInt($.cookie('ugToursCounter_' + blockMachineName)) <= tourDisplayCounter && parseInt($.cookie('ugToursCounter_' + blockMachineName)) > 0) {
        // Init intro js with specific options.
        var introjs = introJs().setOptions(toursSettings).start();
        introjs.onbeforeexit(function () {
          if (!!$.cookie('ugToursCounter_' + blockMachineName)) {
            $.cookie('ugToursCounter_' + blockMachineName, parseInt($.cookie('ugToursCounter_' + blockMachineName)) - 1);
          }
        });
        if (startWithStep > 0) {
          introjs.goToStepNumber(startWithStep);
        }
      }
      if (useHints && parseInt($.cookie('ugToursCounter_' + blockMachineName)) === 0) {
        // Manage Hints cases.
        toursSettings.steps = $.grep(toursSettings.steps, function (step) {
          return step.element !== undefined;
        });
        toursSettings.showProgress = false;
        toursSettings.showButtons = false;
        toursSettings.disableInteraction = false;
        toursSettings.keyboardNavigation = false;
        toursSettings.overlayOpacity = 0;
        toursSettings.showBullets = false;
        toursSettings.showStepNumbers = false;
        var introjsHints = null;
        toursSettings.steps.forEach(function (step, index) {
          $(step.element).mouseenter(function () {
            introjsHints = introJs().setOptions(toursSettings);
            introjsHints.start().goToStepNumber(index+1);
            $('.introjs-overlay').css('z-index', -1);
          });
          $(step.element).mouseleave(function () {
            if (introjsHints) {
              introjsHints.exit();
            }
          });
        });
      }
    },

  };
})(jQuery, Drupal);
