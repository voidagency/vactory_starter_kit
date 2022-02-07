(function($, Drupal) {
  Drupal.behaviors.vactory_flash_news = {
    attach: function (context, settings) {
      animateFlashNews();
    }
  };
  function animateFlashNews() {
    setTimeout(function() {
      items = $('.flash-news-items-wrapper .item');
      $('.flash-news-items-wrapper .item.prev').removeClass('prev');
      for (i = 0; i < items.length; i++) {
        item = $(items[i]);
        if (item.hasClass('active')) {
          item.addClass('prev');
          item.removeClass('active');
          if (i < items.length - 1) {
            nextItem = $(items[i + 1]);
            nextItem.addClass('active');
          }
          else {
            firstItem = $(items[0]);
            firstItem.addClass('active');
          }
          break;
        }
      }
      setTimeout(function() {
        $('.flash-news-items-wrapper .item.prev').removeClass('prev');
      }, 1000);
      animateFlashNews();
    }, 5000);
  }
})(jQuery, Drupal);
