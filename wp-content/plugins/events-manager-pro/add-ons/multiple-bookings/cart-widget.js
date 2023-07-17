jQuery(document).ready( function($){ 
	$(document).on('em_booking_success em_cart_widget_refresh em_cart_refresh',function(){
		$('.em-cart-widget').each( function( i, el ){
			el = $(el);
			var form = el.find('form');
			var formData = form.serialize();
			var loading_text = el.find('.em-cart-widget-contents').text(form.find('input[name="loading_text"]').val());
			$.get( EM.ajaxurl, formData, function( response ){
				loading_text.html( response );
			});
		});
	});
	if( EM.cache ){
		$(document).trigger('em_cart_widget_refresh');
	}
	$(document).on('em_booking_button_response', function(e, response, button){
		if( response.result ){
			$(document).trigger('em_cart_widget_refresh');
			button.addClass('em-booked-button').removeClass('em-booking-button');
			if( typeof response.redirect == 'string' ){
				button.attr('href',response.redirect);
			}
			if( typeof response.text == 'string' ){
				button.text(response.text);
			}
		}
	});
});