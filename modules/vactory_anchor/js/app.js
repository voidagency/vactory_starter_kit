jQuery(document).ready(function ($) {
  $('a.anchor-link').click(function () {
    $('html, body').animate({
      scrollTop: $($.attr(this, 'href')).offset().top
    }, 500);
    window.location.hash = $.attr(this, 'href');
    return false;
  });

  var currentHash = $('.paragraph-anchor').first().attr('id');
  $(document).scroll(function () {
    $('.paragraph-anchor').each(function () {
      var top = window.scrollY;

      var distance = top - $(this).offset().top;
      var hash = $(this).attr('id');
      if (distance < 30 && distance > -30 && currentHash !== hash) {
        window.location.hash = hash;
        currentHash = hash;
      }
    });
  });

});
