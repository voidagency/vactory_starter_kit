(function ($, Drupal, drupalSettings) {
	Drupal.behaviors.vactory_notifications = {
		attach: function (context, settings) {
			// Ensure executing ajax request once.
			if (context !== document) {
				return;
			}
			function updateToasts() {
				$.post('/toasts.php', {
					langcode: drupalSettings.vactory_notifications.langcode,
					uid: drupalSettings.user.uid
				}).done(function (data) {
					if (data.content !== undefined) {
						$('.toast').remove();
						$('#notifications-toast-wrapper').html(data.content);
						$('.toast').toast('show');
					}
				});
			}
			// Update toasts each 3 seconds.
			setInterval(function () {updateToasts();}, 3000);
		}
	};
})(jQuery, Drupal, drupalSettings);