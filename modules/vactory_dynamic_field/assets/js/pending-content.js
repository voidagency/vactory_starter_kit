/**
 * @file
 * Javascript file for pending content.
 */
(function ($, window, Drupal) {
	Drupal.behaviors.vactory_dynamic_field_pending = {
		attach: function (context) {
			if (context !== document) {
				return;
			}
			$("#page-printer").click(function (e) {
				e.preventDefault()
				$("details").attr("open", true)
				window.print()
			})
		}
	}
})(jQuery, window, Drupal);
