
/**
 * Using Youtube API
 */

jQuery(document).ready(function ($) {

  var $window = $(window); // 1. Window Object.
  var $featuredMedia = $("#featured-media"); // 1. The Video Container.
  var $featuredVideo = $("#featured-video"); // 2. The Youtube Video.

  var player; // 3. Youtube player object.
  var top = $featuredMedia.offset().top; // 4. The video position from the top of the document;
  var offset = top + $featuredMedia.outerHeight(); //5. offset.
  var stickyCloseButton = $('#close-sticky-video');

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

  function closeStickyVideo() {
    stickyCloseButton.removeClass('is-active');
    $featuredVideo.removeClass('is-sticky');
    player.pauseVideo();
  }

  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player("featured-video", {
      events: {
        onStateChange: onPlayerStateChange,
      },
    });
    stickyCloseButton.on('click', closeStickyVideo);
  };

  $window.on("resize", function () {
    top = $featuredMedia.offset().top;
    offset = Math.floor(top + $featuredMedia.outerHeight() / 2);
  });
  $window.on("scroll", function () {
    if (matchMedia('(min-width: 992px)').matches) {
      $featuredVideo.toggleClass("is-sticky",$window.scrollTop() > offset && $featuredVideo.hasClass("is-playing"));
      stickyCloseButton.toggleClass("is-active",$window.scrollTop() > offset && $featuredVideo.hasClass("is-sticky"));
    } else {
      $featuredVideo.removeClass('is-sticky');
      stickyCloseButton.removeClass('is-active');
    }
  });

});



/**
 * using only jquery
 */
jQuery(document).ready(function ($) {
  var $stickyBloc = $('.sticky-bloc');
  if ($stickyBloc.length && matchMedia('(min-width: 992px)').matches) {
    $stickyBloc.each(function(e) {
      var _stickyBloc = $(this);
      var _stickyBlocHeight = _stickyBloc.height();
      var _stickyBlocOffset = _stickyBloc.offset().top + _stickyBlocHeight;

      $(window).scroll(function() {
        var _isStickyHeight = _stickyBloc.height();
        if ( $(window).scrollTop() > _stickyBlocOffset + 200 ) {
          // _stickyBloc.parent().height(_stickyBlocHeight);
          _stickyBloc.find('iframe').height(_isStickyHeight);
          _stickyBloc.removeClass('sticky').addClass('sticky-back');
          _stickyBloc.addClass('sticky');
        } else {
          _stickyBloc.removeClass('sticky').removeClass('sticky-back');
          _stickyBloc.parent().height("auto");
          _stickyBloc.find('iframe').height(_stickyBlocHeight);
        };
      });

    });
  }
});
