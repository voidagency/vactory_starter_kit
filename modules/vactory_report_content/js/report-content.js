(function ($, Drupal) {
	function renderForm() {
		var endpoint = Drupal.url('_report-content');
		var executed = Drupal.ajax({ url: endpoint }).execute();
	}
	Drupal.behaviors.vactory_report_content = {
		attach: function (context, settings) {
			if (context === document) {
				var form = $('#js-form-report-content');
				if (form.children().length === 0) {
					console.log('me');
					renderForm();
				}
			}
			$("#report-content-modal").on('hidden.bs.modal', function() {
				console.log('hello');
				$('#js-form-report-content').siblings().remove();
				renderForm();
			});
		},
	};
})(jQuery, Drupal);