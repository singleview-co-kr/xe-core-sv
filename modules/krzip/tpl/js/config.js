(function($) {
	$(function() {
		$("#krzip_address_format").change(function() {
			if ($(this).val() === "postcodify") {
				$("#postcodify_display_customization").show();
			} else {
				$("#postcodify_display_customization").hide();
			}
		}).triggerHandler("change");

		$("#krzip_address_format").change(function() {
			if ($(this).val() === "kakaokrzip") {
				$("#kakaokrzip_display_customization").show();
			} else {
				$("#kakaokrzip_display_customization").hide();
			}
		}).triggerHandler("change");
	});
})(jQuery);