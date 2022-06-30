(function ($, Drupal) {
  var tabs_slider = $('.tabs-container'),
    tabs_slider_link = $('.tabs-slider .nav-item .nav-link'),
    isDesktop = window.matchMedia("(min-width: 992px)").matches,
    interval = null,
    autoplay_index = 0;


  // loading slider image
  function loadImage() {
    var _slider_images = $('.tabs-slider .backgrounds .background'),
      _slider_image_active = $('.tabs-slider .backgrounds .background.is-active'),
      _sliders = $('.tabs-slider'),
      _sliders_item_length = _slider_images.length - 1;

    //_sliders.addClass('loading');
    $.each(_slider_images, function (index, item) {
      var url = (isDesktop) ? $(item).attr('data-image') : $(item).attr('data-imagemobile');

      $('<img src="' + url + '" />').on('load', function () {
        $(item).css('background-image', 'url("' + url + '")');
        // Remove the created dom image
        $(this).remove();

        // Remove loading after loaded all images
        if (index === _sliders_item_length) {
          _slider_image_active.addClass('scales-images');
          _sliders.removeClass('loading');
          if (isDesktop) {
            interval = setInterval(function () {
              autoplayHpSlider();
            }, 5000);
          }
        }
      });
    });
  }

  // Load image of slider Hp
  loadImage();


  // Animation of tabs link
  activeLine = function (position, active, status) {
    if (status === undefined) {
      status = true;
    }
    if (isDesktop) {
      var linkHover = (status) ? active.position() : {top: 0, left: 15};
      if (position === 'left') {
        $(active).parents('.vh-header--menu').find('.header__line').css({
          'left': linkHover.left + 'px',
          'width': (status) ? active.innerWidth() + 'px' : '0px'
        });
      }
      else if (position === 'top') {
        $(active).parents('.tabs-container').find('.header__line-h').css({
          'top': linkHover.top + 'px',
          'height': (status) ? active.innerHeight() + 'px' : '0px',
        });
      }
    }
  };

  // set active state on loading page
  $(window).on('load', function () {
    if (tabs_slider_link.hasClass('active') && isDesktop) {
      var activeSlideLink = $('.tabs-slider .nav-item .nav-link.active');
      activeLine('top', activeSlideLink);
    }
  });


  $('.tabs-slider a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    var $relatedTarget = $(e.relatedTarget); // Previous active tab.
    var $target = $(e.target); // Newly activated tab.
    var $relatedTargetId = $relatedTarget.data('item');
    var $targetId = $target.data('item');

    // Pause old video.
    if ($('.tabs-slider .background.is-video[data-item="' + $relatedTargetId + '"]').length) {
      $('.tabs-slider .background.is-video[data-item="' + $relatedTargetId + '"]').next().YTPPause();
    }

    // Play new video.
    if ($('.tabs-slider .background.is-video[data-item="' + $targetId + '"]').length) {
      $('.tabs-slider .background.is-video[data-item="' + $targetId + '"]').next().YTPPlay();
    }

    // Toggle active state transition.
    $('.tabs-slider .background[data-item="' + $relatedTargetId + '"]').removeClass('is-active').removeClass('scales-images');
    $('.tabs-slider .background[data-item="' + $targetId + '"]').addClass('is-active').addClass('scales-images');

  });

  // Pause all videos by default.
  // Todo: play pause video.

  // On mobile trigger the click on tabs when swiping the slider
  $('.tabs-slider .tab-content').on('afterChange', function (event, slick, currentSlide) {
    var targetTab = currentSlide + 1;
    var targetLink = $(".tabs-slider .nav-link[data-item='" + targetTab + "']");
    targetLink.trigger('click');
  });

  // function to autoplay hp slider
  function autoplayHpSlider() {
    var HpSliderItem = $('.tabs-slider .nav-tabs .nav-item');
    autoplay_index = (autoplay_index >= HpSliderItem.length - 1) ? 0 : autoplay_index + 1;
    HpSliderItem.eq(autoplay_index).find('.nav-link').trigger('click');
    activeLine('top', HpSliderItem.eq(autoplay_index).find('.nav-link'));
  }

  $('.tabs-slider .nav-tabs .nav-link').on('click', function () {
    var indexclicked = $(this).parent().index() - 1;
    autoplay_index = indexclicked;
    activeLine('top', $(this));
    clearInterval(interval);
    interval = setInterval(function () {
      autoplayHpSlider();
    }, 5000);
  });

})(jQuery, Drupal);
