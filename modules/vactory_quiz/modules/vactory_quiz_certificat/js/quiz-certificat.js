(function ($, drupalSettings, Drupal) {
	Drupal.behaviors.vactory_quiz_certificat = {
		attach: function (context, settings) {
			if (context !== document) {
				return;
			}
			$('#print-certificat').on('click', function (e) {
				$('#printable').print({
					noPrintSelector: '#print-certificat',
				});
			});
		}
	};
})(jQuery, drupalSettings, Drupal);