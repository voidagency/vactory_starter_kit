(function ($, Drupal, drupalSettings) {
	Drupal.behaviors.vactory_notifications = {
		attach: function (context, settings) {
			// Ensure executing ajax request once.
			if (context !== document) {
				return;
			}
			function updateToasts() {
				$('.toast').remove();
				$.get('/notifications-toast', function (data) {
					if (data.content !== undefined) {
						$('#notifications-toast-wrapper').html(data.content);
						$('.toast').toast('show');
					}
				});
			}
			// Update toasts each 15 seconds.
			setInterval(function () {updateToasts();}, 15000);
		}
	};
})(jQuery, Drupal, drupalSettings);