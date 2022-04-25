(function ($, Drupal, drupalSettings) {
	Drupal.behaviors.vactory_form_autosave = {
		attach: function (context, settings) {
			if (context !== document) {
				return;
			}
			var concernedForms = $('form.form-autosave');
			concernedForms.each(function (index, form) {
				// Load form draft if exist.
				var form_id = $(this).attr('data-real-fid');
				var data = drupalSettings.vactory_form_autosave[form_id].data;
				if (data) {
					$(form).values(JSON.parse(data));
				}
			});
			concernedForms.change(function (e) {
				// Update form draft whenever it is changed.
				var values = $(this).values();
				$.ajax({
					type: "POST",
					url: '/vactory-form-autosave/update',
					data: {
						formData: JSON.stringify(values),
						formId: $(this).attr('data-real-fid'),
					},
					success: function (response) {
						console.log(response);
					},
				});
			});
		}
	};

	// jQuery plugin to get/update form data.
	$.fn.values = function(data) {
		var els = $(this).find(':input').get();
		if(typeof data != 'object') {
			// return all data
			data = {};
			$.each(els, function() {
				if (
					this.name && !this.disabled && (
						this.checked ||
						/select|textarea/i.test(this.nodeName) ||
						/text|hidden|email|date|file|number|tel|time/i.test(this.type)
					)) {
					data[this.name] = $(this).val();
				}
			});
			return data;
		} else {
			$.each(els, function() {
				if (this.name && data[this.name]) {
					if(this.type === 'checkbox' || this.type === 'radio') {
						$(this).attr("checked", (data[this.name] === $(this).val()));
					} else {
						$(this).val(data[this.name]);
					}
				}
			});
			return $(this);
		}
	};

})(jQuery, Drupal, drupalSettings);