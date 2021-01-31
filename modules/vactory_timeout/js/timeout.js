(function ($, Drupal, drupalSettings) {
  $(function () {
    if (drupalSettings.vactory.timeout_active) {
      var timeoutPopUp = 15;
      var x;
      var popUp = '<div class="modal fade Confirmeredirection" id="confirmeredirection" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"><div class="modal-dialog m-0" role="document"> <div class="modal-content box-gradient p-0"> <div class="modal-body test-white border-none text-center"> <h3>' + Drupal.t('Voulez-vous continuer ?') + '</h3> </div> <div class="modal-footer align-center justify-content-center d-flex flex-row"><a  class="btn btn-outline-light mx-2 iframepopupnon">' + Drupal.t('Non') + '</a><a  class="btn btn-secondary mx-2 iframepopupyes" data-dismiss="modal">' + Drupal.t('Oui') + '</a> </div> </div> </div></div>';

      function startCountDown(countDowndate) {
        clearInterval(x);
        x = setInterval(function (countDownDate) {
          // Get today's date and time
          var now = new Date().getTime();
          // Find the distance between now and the count down date
          var distance = countDownDate - now;
          // If the count down is finished, write some text

          if (($('.fancybox-is-open').length === 0) || ($('.fancybox-is-open').length !== 0 && $('#confirmeredirection').hasClass('show'))) {
            if (distance < 0) {
              clearInterval(x);
              window.location.replace(drupalSettings.vactory.landing_page);
            }
          }
          else if ($('#confirmeredirection').hasClass('show')) {
            if (distance < 0) {
              clearInterval(x);
              $('#confirmeredirection').modal('show');
            }
          }
          else {
            if (distance < 0) {
              clearInterval(x);
              $('#confirmeredirection').modal('show');
            }
          }
        }, 1000, countDowndate);
      }

      if (drupalSettings.vactory.current_page !== drupalSettings.vactory.landing_page) {
        if ($('a[data-fancybox]').length > 0) {
          $('body').append(popUp);
        }
        $(document).click(function () {
          if ($('.fancybox-is-open').length === 0) {
            var date = new Date();
            var countDowndate = new Date(date.getFullYear(), date.getMonth(), date.getUTCDate(), date.getHours(), date.getMinutes(), date.getSeconds() + parseInt(drupalSettings.vactory.timeout, 10)).getTime();
            startCountDown(countDowndate);
          }
        });
        $(window).on('load', function () {
          var date = new Date();
          var countDowndate = new Date(date.getFullYear(), date.getMonth(), date.getUTCDate(), date.getHours(), date.getMinutes(), date.getSeconds() + parseInt(drupalSettings.vactory.timeout, 10)).getTime();
          startCountDown(countDowndate);
        });
        $(document).on('click', '.iframepopupyes', function () {
          var date = new Date();
          var countDowndate = new Date(date.getFullYear(), date.getMonth(), date.getUTCDate(), date.getHours(), date.getMinutes(), date.getSeconds() + parseInt(drupalSettings.vactory.timeout, 10)).getTime();
          startCountDown(countDowndate);
        });
        $(document).on('click', '.iframepopupnon', function () {
          clearInterval(x);
          window.location.replace(drupalSettings.vactory.landing_page);
        });
        $('#confirmeredirection').on('show.bs.modal', function () {
          var date = new Date();
          var countDowndate = new Date(date.getFullYear(), date.getMonth(), date.getUTCDate(), date.getHours(), date.getMinutes(), date.getSeconds() + parseInt(timeoutPopUp, 10)).getTime();
          startCountDown(countDowndate);
        })
        $('a[data-fancybox]').fancybox({
          afterShow: function () {
            var date = new Date();
            var countDowndate = new Date(date.getFullYear(), date.getMonth(), date.getUTCDate(), date.getHours(), date.getMinutes(), date.getSeconds() + parseInt(drupalSettings.vactory.timeout, 10)).getTime();
            startCountDown(countDowndate);
          },
          afterClose: function () {
            var date = new Date();
            var countDowndate = new Date(date.getFullYear(), date.getMonth(), date.getUTCDate(), date.getHours(), date.getMinutes(), date.getSeconds() + parseInt(drupalSettings.vactory.timeout, 10)).getTime();
            startCountDown(countDowndate);
          }
        });
      }
    }


  });
})(jQuery, Drupal, drupalSettings);
