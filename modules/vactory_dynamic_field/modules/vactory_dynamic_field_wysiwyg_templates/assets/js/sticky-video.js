/**
 * Using Youtube API
 */

jQuery(document).ready(function ($) {
  var $window = $(window), // 1. Window Object.
    $featuredMedia = $("#featured-media"), // 1. The Video Container.
    $featuredVideo = $("#featured-video"), // 2. The Youtube Video.
    player, // 3. Youtube player object.
    top, // 4. The video position from the top of the document;
    offset, //5. offset.
    stickyCloseButton = $("#close-sticky-video");

  function videoPosition() {
    top = Math.floor($featuredMedia.position().top);
    offset = Math.floor(top - $featuredMedia.outerHeight());
  }

  function onPlayerStateChange(event) {
    var isPlay = 1 === event.data;
    var isPause = 2 === event.data;
    var isEnd = 0 === event.data;

    if (isPlay) {
      $featuredVideo.removeClass("is-paused");
      $featuredVideo.addClass("is-playing");
    }
    if (isPause) {
      $featuredVideo.removeClass("is-playing");
      $featuredVideo.addClass("is-paused");
    }
    if (isEnd) {
      $featuredVideo.removeClass("is-playing", "is-paused");
    }
  }

  function closeStickyVideo() {
    stickyCloseButton.removeClass("is-active");
    $featuredVideo.removeClass("is-sticky");
    player.pauseVideo();
  }

  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player("featured-video", {
      events: {
        onStateChange: onPlayerStateChange,
      },
    });
    stickyCloseButton.on("click", closeStickyVideo);
  };

  videoPosition();

  $window.on("resize", function () {
    videoPosition();
  });

  $window.on("scroll", function () {
    videoPosition();
    $featuredVideo.toggleClass(
      "is-sticky",
      $window.scrollTop() > top && $featuredVideo.hasClass("is-playing")
    );
    stickyCloseButton.toggleClass(
      "is-active",
      $window.scrollTop() > top && $featuredVideo.hasClass("is-sticky")
    );
    if ($window.scrollTop() > offset) {
      player.mute();
      player.playVideo();
    } else {
      if ($featuredVideo.hasClass("is-playing")) {
        player.pauseVideo();
      }
    }
  });
});

/**
 * using only jquery
 */
jQuery(document).ready(function ($) {
  var $stickyBloc = $(".sticky-bloc");
  if ($stickyBloc.length && matchMedia("(min-width: 992px)").matches) {
    $stickyBloc.each(function (e) {
      var _stickyBloc = $(this);
      var _stickyBlocHeight = _stickyBloc.height();
      var _stickyBlocOffset = _stickyBloc.offset().top + _stickyBlocHeight;

      $(window).scroll(function () {
        var _isStickyHeight = _stickyBloc.height();
        if ($(window).scrollTop() > _stickyBlocOffset + 200) {
          // _stickyBloc.parent().height(_stickyBlocHeight);
          _stickyBloc.find("iframe").height(_isStickyHeight);
          _stickyBloc.removeClass("sticky").addClass("sticky-back");
          _stickyBloc.addClass("sticky");
        } else {
          _stickyBloc.removeClass("sticky").removeClass("sticky-back");
          _stickyBloc.parent().height("auto");
          _stickyBloc.find("iframe").height(_stickyBlocHeight);
        }
      });
    });
  }
});
