(function ($, Drupal, drupalSettings) {
	Drupal.behaviors.vactory_notifications = {
		attach: function (context, settings) {
			// Ensure executing ajax request once.
			if (context !== document) {
				return;
			}
			// Update toasts each 2 seconds.
			var interval = 2000;
			var langcode = drupalSettings.vactory_notifications.langcode;
			function updateToasts() {
				$.ajax({
					type: 'GET',
					url: '/' + langcode + '/toasts',
					success: function (data) {
						if (data.content !== undefined) {
							$('.toast').remove();
							$('#notifications-toast-wrapper').html(data.content);
							$('.toast').toast('show');
						}
					},
					complete: function (data) {
						// Schedule the next toast.
						setTimeout(updateToasts, interval);
					}
				});
			}
			setTimeout(updateToasts, interval);
		}
	};
})(jQuery, Drupal, drupalSettings);