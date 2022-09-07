(function ($) {
  $(document).ready(function () {
    if(!sessionStorage.getItem("WelcomePopup")) {
      sessionStorage.setItem("WelcomePopup", "empty");
      $("#vactoryWelcomeModal").modal("show");
    } else if (sessionStorage.getItem("WelcomePopup") == "empty") {
      $("#vactoryWelcomeModal").modal("show");
    }
    $(".close-welcome").click(function (e) {
      e.preventDefault();
      sessionStorage.setItem("WelcomePopup", "false");
    });
  });
})(jQuery);

