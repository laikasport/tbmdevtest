var em_setup_attendee_forms = function( spaces, fieldset = null, fieldset_container, fields_template){
	if( fields_template.length === 0 ) return;
	fields_template.find('input.em-date-input-loc').datepicker('destroy').attr('id', ''); //clear all datepickers
	fields_template.find(".em-datepicker .em-date-input").each( function(){
	    if( '_flatpickr' in this ){
	        this._flatpickr.destroy();
	    }
	});
	fields_template.find('.em-time-range input.em-time-end, .em-time-range input.em-time-start').unbind(['click','focus','change']); //clear all timepickers - consequently, also other click/blur/change events, recreate the further down
	//get the attendee form template and clone it
	var form = fields_template.clone().removeClass('em-ticket-booking-template').addClass('em-ticket-booking');
	//add or subtract fields according to spaces
	var current_forms = fieldset_container.find('.em-ticket-booking');
	var new_forms = [];
	if( current_forms.length < spaces ){
		// we're adding new forms, so we clone our newly cloned and trimmed template form and keep adding it before the template, which is last
		for( var i= current_forms.length ; i < spaces; i++ ){
			var new_form = form.clone().insertBefore(fields_template).show();
			new_form.html(new_form.html().replace(/#NUM#/g,i+1));
			new_form.find('*[name]').each( function(it, el){
				el = $(el);
				el.attr('name', el.attr('name').replace('[%n]','['+i+']'));
			});
			new_forms.push(new_form);
		}
	}else if( current_forms.length > spaces ){
		var current_forms_length = current_forms.length;
		for( var i= spaces; i < current_forms_length; i++ ){
			current_forms.last().remove();
			current_forms.splice(current_forms.length-1,1);
		}
	}
	//clean up
	new_forms.forEach( function( new_form ){
		em_setup_datepicker(new_form);
		em_setup_timepicker(new_form);
		//form tooltips - delete all and recreate events
		new_form.find('span.form-tip').each( function(it, el){
			if( typeof el._tippy !== "object" ){
				var tooltip_vars = { theme : 'light-border', placement : 'right', allowHTML: true };
				$(document).trigger('emp-tippy-vars',[tooltip_vars]);
				tippy(el, tooltip_vars);
			}
		});
	});
	form.remove();
	return true;
};
$('.em-booking-form .em-ticket-select').on('change', function(e){
	var el = $(this);
	var spaces = el.val();
	var ticket_id = el.attr('data-ticket-id')
	var fieldset_container = el.closest('.em-booking-form').find('.em-ticket-bookings-'+ticket_id);
	var fields_template = fieldset_container.find('.em-ticket-booking-template');
	em_setup_attendee_forms(spaces, null, fieldset_container, fields_template);
	if( spaces > 0 ){
		fieldset_container.show().removeClass('hidden');
	}else{
		fieldset_container.hide().addClass('hidden');
	}
});
//(re)load attendee forms for fields with spaces pre-selected
$('.em-booking-form .em-ticket-select').each( function(){
	var el = $(this);
	if( el.val() != 0 ) el.trigger('change');
});

$('.em-booking-form .em-ticket-booking .em-ticket-booking-remove-trigger').on('click', function(){
	let el = $(this)
	let wrapper = el.closest('.em-ticket-bookings');
	let ticket_id =	wrapper.attr('data-ticket-id');
	el.closest('.em-ticket-booking').remove();
	let select = wrapper.closest('.em-booking-form').find('.em-ticket-'+ticket_id+' .em-ticket-select');
	select.val( select.val() - 1 );
});