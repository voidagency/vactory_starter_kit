/**
 * @file
 * Vactory Search Overlay Variant 2.
 */

(function ($, Drupal) {

  'use strict';

  $(document).ready(function () {

    var _searchOverlay = $('.search-overlay-variant2'),
      search_overlay_button = $('.vh-header--search-button > a, a.vh--search-button, .btn-search-overlay, .close-search-overlay'),
      inputSearch = _searchOverlay.find('.search_input');
    $('.search-block-form').each(function () {
      inputSearch.keypress(function (e) {
        // Enter pressed submit the form.
        if (e.which === 10 || e.which === 13) {
          // Submit the form.
          this.form.submit();
        }
      });
    });
    $(document).on('keyup', function (key) {
      if (key.keyCode === 27 && _searchOverlay.hasClass('open')) {
        _searchOverlay.removeClass('open');
        if (window.matchMedia("(max-width: 992px)").matches && $('body').hasClass('overflow-y')) {
          $('body').removeClass('overflow-y');
        }
      }
    });
    search_overlay_button.on('click', function (event) {
      event.preventDefault();
      if (!_searchOverlay.hasClass('open')) {
        _searchOverlay.addClass('open');
        if (window.matchMedia("(max-width: 992px)").matches && !$('body').hasClass('overflow-y')) {
          $('body').addClass('overflow-y');
        }

        // create invisible dummy input to receive the focus first
        var fakeInput = document.createElement('input');
        fakeInput.setAttribute('type', 'text');
        fakeInput.style.position = 'absolute';
        fakeInput.style.opacity = 0;
        fakeInput.style.height = 0;
        fakeInput.style.fontSize = '16px';
        document.body.prepend(fakeInput);
        fakeInput.focus();

        setTimeout(function () {
          inputSearch.focus();
          fakeInput.remove();
        }, 600);
      }
      else {
        _searchOverlay.removeClass('open');
        if (window.matchMedia("(max-width: 992px)").matches && $('body').hasClass('overflow-y')) {
          $('body').removeClass('overflow-y');
        }
      }
    });
  });
}(jQuery, Drupal));
