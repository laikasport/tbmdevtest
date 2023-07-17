document.querySelectorAll('form.em-waitlist-booking-cancel').forEach( function(form){
    form.addEventListener('em_ajax_form_success_waitlist_cancel', function(e){
        EM_Booking_Success_Cleanup( form );
    });
});
document.addEventListener('em_booking_success', function(e){
    EM_Booking_Success_Cleanup( e.detail.form );
});
function EM_Booking_Success_Cleanup( form ){
    let wrapper = form.closest('.em-waitlist-booking-approved');
    let parent = form.closest('div');
    if( wrapper !== null && wrapper !== parent ){
        let classes = parent.getAttribute('class').replaceAll(' ', '.');
        let others = wrapper.querySelectorAll(':scope > div:not(.'+ classes +')');
        if( others !== null ){
            others.forEach( function( el ){ el.remove(); });
        }
    }
}