jQuery(document).ready(function ($) {

  var $window = $(window); // 1. Window Object.
  var $featuredMedia = $("#featured-media"); // 1. The Video Container.
  var $featuredVideo = $("#featured-video"); // 2. The Youtube Video.

  var player; // 3. Youtube player object.
  var top = $featuredMedia.offset().top; // 4. The video position from the top of the document;
  var offset = top + $featuredMedia.outerHeight(); //5. offset.

  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player("featured-video", {
      events: {
        onStateChange: onPlayerStateChange,
      },
    });
  };

  function onPlayerStateChange(event) {
    var isPlay = 1 === event.data;
    var isPause = 2 === event.data;
    var isEnd = 0 === event.data;

    if (isPlay) {
      $featuredVideo.removeClass("is-paused");
      $featuredVideo.toggleClass("is-playing");
    }
    if (isPause) {
      $featuredVideo.removeClass("is-playing");
      $featuredVideo.toggleClass("is-paused");
    }
    if (isEnd) {
      $featuredVideo.removeClass("is-playing", "is-paused");
    }
  }

  $window.on("resize", function () {
    top = $featuredMedia.offset().top;
    offset = Math.floor(top + $featuredMedia.outerHeight() / 2);
  });
  $window.on("scroll", function () {
    if (matchMedia('(min-width: 992px)').matches) {
      $featuredVideo.toggleClass(
        "is-sticky",
        $window.scrollTop() > offset && $featuredVideo.hasClass("is-playing")
      );
    } else {
      $featuredVideo.removeClass('is-sticky');
    }
  });
});
