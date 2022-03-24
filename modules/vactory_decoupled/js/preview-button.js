jQuery(document).ready(function ($) {
  $('a#frontend-preview-button').click(function () {
    return confirm('Please make sure your changes are saved before preview.');
  });
});
