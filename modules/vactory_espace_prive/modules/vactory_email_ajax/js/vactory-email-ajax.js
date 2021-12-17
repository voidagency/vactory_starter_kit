(function($, Drupal) {
	Drupal.behaviors.vactory_email_ajax = {
		attach: function (context, settings) {
			$('#account-email').on('input', function (e) {
				var email = e.target.value;
				$.post(
					'/email/validate',
					{ email: email }
				).done(function(data) {
					if (!data.is_valid) {
						$('#account-email').addClass('border-danger');
						$('#invalid-account-email-message').text(Drupal.t("Adresse e-mail invalide, veuillez essayer avec une autre"));
					}
					else {
						$('#account-email').removeClass('border-danger');
						$('#invalid-account-email-message').text('');
					}
				});
			});
		}
	};
})(jQuery, Drupal);
