(function ($, Drupal) {
  "use strict";

  const uixToolbox = ".uix-toolbox";
  const uixToolboxTrigger = ".uix-toolbox__trigger";

  function showToolboxItem(selector) {
    $(selector).find("ul").addClass("visible");
    $(selector)
      .find("a")
      .each(function (e, i) {
        $(this).addClass("visible");
      });
  }

  function hideToolboxItem(selector) {
    $(selector).find("ul").removeClass("visible");
    $(selector)
      .find("a")
      .each(function (e, i) {
        $(this).removeClass("visible");
      });
  }

  function hideToolboxOnClickOutside(selector) {
    const outsideClickListener = (event) => {
      const $target = $(event.target);
      if (
        !$target.closest(selector).length &&
        $(selector).find("ul").hasClass("visible")
      ) {
        hideToolboxItem(selector);
        removeClickListener();
      }
    };

    const removeClickListener = () => {
      document.removeEventListener("click", outsideClickListener);
    };

    document.addEventListener("click", outsideClickListener);
  }

  $(uixToolboxTrigger).on("tap, click", function () {
    if ($(uixToolbox).find("ul").hasClass("visible")) {
      hideToolboxItem(uixToolbox);
    } else {
      showToolboxItem(uixToolbox);
      hideToolboxOnClickOutside(uixToolbox);
    }
  });

})(jQuery, Drupal);
