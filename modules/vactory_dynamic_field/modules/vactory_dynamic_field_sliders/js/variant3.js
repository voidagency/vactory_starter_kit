jQuery(document).ready(function ($) {
  var currentIndx = 0,
    interval = null;
  $('.vf-slider--tabs-slider a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    var $relatedTarget = $(e.relatedTarget); // Previous active tab.
    var $target = $(e.target); // Newly activated tab.
    var $relatedTargetId = $relatedTarget.data('item');
    var $targetId = $target.data('item');
    var bgColor = $(this).data('color');

    // $(this).css('background',bgColor);
    // $('.vf-slider--tabs-slider
    // a[data-toggle="tab"]').not(this).removeAttr('style');

    // Pause old video.
    if ($('.vf-slider--tabs-slider .background.is-video[data-item="' + $relatedTargetId + '"]').length) {
      $('.vf-slider--tabs-slider .background.is-video[data-item="' + $relatedTargetId + '"]').next().YTPPause();
    }

    // Play new video.
    if ($('.vf-slider--tabs-slider .background.is-video[data-item="' + $targetId + '"]').length) {
      $('.vf-slider--tabs-slider .background.is-video[data-item="' + $targetId + '"]').next().YTPPlay();
    }

    // Toggle active state transition.
    $('.vf-slider--tabs-slider .background[data-item="' + $relatedTargetId + '"]').removeClass('is-active');
    $('.vf-slider--tabs-slider .background[data-item="' + $targetId + '"]').addClass('is-active');
  });

  function loadImageSlider() {
    var tabsSlider = $('.vf-slider--tabs-slider'),
      images = tabsSlider.find('.background');
    images.addClass('load-bg');

    loadImageSliderIndex(0);
  }

  /* load image */
  function loadImageSliderIndex(indx) {
    var tabsSlider = $('.vf-slider--tabs-slider'),
      images = tabsSlider.find('.background'),
      sliderLength = images.length - 1,
      item = images.eq(indx),
      url = $(item).attr('data-media');
    if (url) {
      $('<img src="' + url + '" />').on('load', function () {
        $(item).css('background-image', 'url("' + url + '")');
        // Remove the created dom image
        $(this).remove();
        $(item).removeClass('load-bg');
        // Remove loading after load the first item image
        if (indx === 0) {
          tabsSlider.removeClass('onload');
          $('body').addClass('has-loaded');
        }
        if (indx < sliderLength) {
          indx++;
          loadImageSliderIndex(indx);
        }
        else {
          interval = setInterval(function () {
            enableAutoPlayHpSlider();
          }, 2000);
        }

      });
    }
  }

  // function to autoplay hp slider
  function enableAutoPlayHpSlider() {
    var tabsSliderItem = $('.vf-slider--tabs-slider .nav-tabs .nav-item');
    currentIndx = (currentIndx >= tabsSliderItem.length - 1) ? 0 : currentIndx + 1;
    tabsSliderItem.eq(currentIndx).find('.nav-link').trigger('click');
  }

  $('.vf-slider--tabs-slider .nav-tabs .nav-link').on('click', function () {
    var itemactive = $(this).parent().index();
    currentIndx = itemactive;
    clearInterval(interval);
    interval = setInterval(function () {
      enableAutoPlayHpSlider();
    }, 2000);
  });

  loadImageSlider();

  // Pause all videos by default.
  // Todo: play pause video.

});
