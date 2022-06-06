(function($, Drupal){
  "use strict";

  $(function() {

    var $tab_slider = $('.vf-slider-tab__slider');
    var indicators = $(".vf-slider-tab__tab-indicator");
    var tab_item = $('.vf-slider-tab__tab-item');

    var slider_option = {
      rtl : Drupal.vars.vactory.is_rtl,
      infinite: false,
      slidesToShow: 1,
      slidesToScroll: 1,
      dots: false,
      arrows: ($tab_slider .data('arrows') !== undefined) ? $tab_slider.data('arrows') : true,
      autoplay: ($tab_slider .data('autoplay') !== undefined) ? $tab_slider.data('autoplay') : true,
      autoplaySpeed: ($tab_slider .data('autoplayspeed') !== undefined) ? $tab_slider.data('autoplayspeed') : '2000',
      fade: ($tab_slider .data('fade') !== undefined) ? $tab_slider.data('fade') : false,
      speed: ($tab_slider .data('animationspeed') !== undefined) ? $tab_slider.data('animationspeed') : '500',
    };

    function update_indicator() {
      var active_tab = $(".vf-slider-tab__tab-item.is-active");
      var active_tab_position = active_tab.position();
      var active_tab_width = active_tab.css('width');
      indicators.css('left', active_tab_position.left);
      indicators.css('width', active_tab_width);
    }

    function mobile_scroll_tab(item) {
      if (window.matchMedia('max-width: 991.98px')) {
        var itemHalfWidth = item.width() / 2; // center width of active item
        var itemPosition = item.position().left + itemHalfWidth; // active position left + center width
        var parentScrollPosition = item.parent().scrollLeft(); // parent scroll posiiton
        var parentWidth = item.parent().width(); // parent width
        itemPosition = itemPosition + parentScrollPosition - parentWidth / 2;
        item.parent().animate({
          scrollLeft: itemPosition
        }, 1000);
      }
    }

    function update_active_tab(index) {
      tab_item.each(function() {
        if($(this).data('index') == index) {
          $(this).addClass('is-active');
          mobile_scroll_tab($(this));
        } else {
          $(this).removeClass('is-active');
        }
      });
    }

    $( window ).resize(function() {
      update_indicator();
    });

    $tab_slider.on('init', function () {
      update_active_tab(0);
      update_indicator();
    });

    $tab_slider.on('beforeChange', function(event, slick, currentSlide, nextSlide){
      update_active_tab(nextSlide);
      update_indicator();
    });

    $tab_slider.slick(slider_option);

    $('.vf-slider-tab').on('click', '.vf-slider-tab__tab-item', function(e) {
      var $tab_slide_index = $(this).data('index');

      $tab_slider.slick('slickGoTo',$tab_slide_index);
      update_active_tab($tab_slide_index);
      update_indicator();
    });
  });
})(jQuery, Drupal);
