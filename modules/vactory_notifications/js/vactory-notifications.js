(function ($, Drupal, drupalSettings) {
	var confirmCallback = function () {
		$('#edit-submit').click(function(e){
			if(confirm(Drupal.t('Attention! En cliquant sur "OK" une notification sera générée pour ce contenu! si vous ne souhaiter pas encore générer de notification veuillez cliquer "Annuler" et décocher la case "Generate notification"'))){
				return true;
			}
			else{
				e.preventDefault();
				$('#edit-node-notifications').removeAttr('close').attr('open', true);
				$('#edit-generate-notification-wrapper').addClass('generate-notification-blink');
				document.getElementById("edit-node-notifications").scrollIntoView();
			}
		});
	}
	Drupal.behaviors.vactory_notifications = {
		attach: function (context, settings) {
			var generateNotificationCheckbox = $('#edit-generate-notification-value');
			generateNotificationCheckbox.on('click', function () {
				if($(this).is(':checked')) {
					confirmCallback();
				}
				else {
					$('#edit-submit').off('click');
					$('#edit-generate-notification-wrapper').removeClass('generate-notification-blink');
				}
			});
			if (generateNotificationCheckbox.is(':checked')) {
				confirmCallback();
			}
		}
	}
})(jQuery, Drupal, drupalSettings)