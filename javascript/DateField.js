(function($) {
	/**
	 * @note This method has been added so that 'showcalendar' is supported when pushing a DateField
	 *       onto a FieldGroup.
	 */
	$('.field.date input.text, .fieldgroupField .date input.text').live('click', function() {
		var holder = $(this).parents('.field.date:first, .fieldgroupField .date:first'), config = holder.metadata();
		if(!config.showcalendar) return;
		
		if(config.locale && $.datepicker.regional[config.locale]) {
			config = $.extend(config, $.datepicker.regional[config.locale], {});
		}
		
		// Initialize and open a datepicker 
		// live() doesn't have "onmatch", and jQuery.entwine is a bit too heavyweight for this, so we need to do this onclick.
		$(this).datepicker(config);
		$(this).datepicker('show');
	});
}(jQuery));