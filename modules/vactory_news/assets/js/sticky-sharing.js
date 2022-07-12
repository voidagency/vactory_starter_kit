(function($, Drupal) {
  $(function() {
    if (!$('.page--vactory-news').length) {
      return;
    } else {
      if (matchMedia("(min-width: 1024px)").matches) {
        var headerHeight = $('#vh-header').outerHeight(true);

        var $sharingWrapper = $('.vactory-news__sharing');
        var $sharingBloc = $sharingWrapper.find('.block-share');
        var $newsHead = $('.vactory-news__head');
        var $newsInner = $('.vactory-news__inner');

        var titleBoundingRect, newsBoundingRect, windowScrollTop, windowHeight, windowBottom, windowWidth, sharingWrapperOffsetTop, sharingComputedPosRight;

        $(window).on('load resize scroll', function(event) {

          windowScrollTop = $(this).scrollTop();
          windowHeight = $(this).innerHeight();
          windowWidth = $(this).width();
          windowBottom = windowScrollTop + windowHeight;
          titleBoundingRect = $($newsHead)[0].getBoundingClientRect();
          newsBoundingRect = $($newsInner)[0].getBoundingClientRect();
          sharingWrapperOffsetTop = $sharingWrapper.offset().top;

          var shareNotVisible = (windowBottom < sharingWrapperOffsetTop);
          var shareVisible = (windowBottom > sharingWrapperOffsetTop);
          var titleVisible = (titleBoundingRect.bottom > headerHeight);
          var titleNotVisible = (titleBoundingRect.bottom < headerHeight);
          var title_share_visible = (titleVisible && shareVisible);

          function stickAfterTitle() {
            $sharingBloc.addClass('block-share--sticky-top').css({'top': titleBoundingRect.bottom, 'right': newsBoundingRect.right, 'max-width': newsBoundingRect.left});
          }
          function stickAfterHeader() {
            $sharingBloc.addClass('block-share--sticky-top').css({'top': headerHeight, 'right': newsBoundingRect.right, 'max-width': newsBoundingRect.left});
          }
          function unStick() {
            $sharingBloc.removeClass('block-share--sticky-top').removeAttr('style');
            return;
          }

          if (shareVisible) {
            unStick();
          } else if(shareNotVisible && titleNotVisible) {
            stickAfterHeader();
          } else {
            stickAfterTitle();
          }

        });
      }
    }

  });
})(jQuery,Drupal);
