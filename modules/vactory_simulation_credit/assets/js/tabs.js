(function ($, Drupal, drupalSettings) {
  $(document).ready(function(){
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      localStorage.setItem('activeTab', $(e.target).attr('href'));
    });
    var activeTab = localStorage.getItem('activeTab');
    if(activeTab){
      var _timer = setTimeout(function () {
        $('.nav-tabs a[href="' + activeTab + '"]').click();
        clearTimeout(_timer);
      }, 300);
    }
  });
})(jQuery, Drupal, drupalSettings);
