jQuery(document).ready(function ($) {

  var isDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
    _IS_MOBILE_VIEW = (matchMedia("only screen and (max-width: 992px)").matches) ? true : false,
    ancreHeight = 70;

  var lastScrollTop = $(window).scrollTop();

  if (!isDevice || !_IS_MOBILE_VIEW) {
    // Clone vactory-ancre to fixed cloned one on scroll
    $('.vactory-ancre').clone().addClass('cloned').insertAfter('.vactory-ancre');
  }

  $('a.anchor-link').click(function () {
    if ((window.matchMedia("(max-width: 992px)").matches)) {
      var _timer = setTimeout(function () {
        $('html, body').animate({
          scrollTop: $($self.attr('href')).offset().top - $('.vh-sticky').outerHeight(true)
        }, 500);
        clearTimeout(_timer);
      }, 300);
    } else {
      $('html, body').animate({
        scrollTop: $($.attr(this, 'href')).offset().top - ($('.vactory-ancre.cloned').outerHeight(true) + 10)
      }, 500);
    }
    window.location.hash = $.attr(this, 'href');
    return false;
  });

  var currentHash = $('.paragraph-anchor').first().attr('id');
  $(window).scroll(function () {
    var windowScrollTop = $(this).scrollTop();

    var anchorPosition = $('.vactory-ancre:not(.cloned)').offset().top;
    if (windowScrollTop >= anchorPosition) {
      $('.vactory-ancre.cloned').addClass('fixed');
    }
    else {
      $('.vactory-ancre.cloned').removeClass('fixed');
    }

    if (windowScrollTop < lastScrollTop) {
      $('.vactory-ancre.cloned').removeClass('bottom').addClass('top');
    } else {
      $('.vactory-ancre.cloned').removeClass('top').addClass('bottom');
    }

    $('.paragraph-anchor').each(function () {
      var paragraphTop = $(this).offset().top - ancreHeight,
        paragraphBottom = $(this).outerHeight(true) + paragraphTop;
      var hash = $(this).attr('id');

      if (windowScrollTop >= paragraphTop && windowScrollTop <= paragraphBottom) {
        $('.vactory-ancre.cloned, .vactory-ancre:not(.cloned)')
          .find('.nav-link[href="#' + $(this).attr('id') + '"]')
          .addClass('active')
          .parent()
          .siblings()
          .find('.nav-link')
          .removeClass('active');

        if (window.history.pushState) {
          // Update the address bar
          window.history.pushState({}, '', '#' + hash);
          // // Trigger a custom event which mimics hashchange
        } else {
          // Fallback for the poors browsers which do not have pushState
          window.location.hash = hash;
        }
      }
    });

    lastScrollTop = windowScrollTop;
  });

  // Fix banner margin 
  $('.vp_anchor').parents().find('.vf-banner').addClass('no-padding');
});