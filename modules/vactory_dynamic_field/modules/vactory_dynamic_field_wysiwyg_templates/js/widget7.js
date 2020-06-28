jQuery(document).ready(function ($) {
  $('a.dynamic-field-widget--play').click(function (e) {
    e.preventDefault();

    // Display/Hide
    $(this).removeClass('d-block').addClass('d-none');
    $(this).next('.video').removeClass('d-none').addClass('d-block');

    // Play video.
    $(this).next('.video').find('.ytplayer').YTPPlay();
  });
});