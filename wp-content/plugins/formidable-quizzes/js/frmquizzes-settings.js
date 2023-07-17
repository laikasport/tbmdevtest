( function( $ ) {
	'use strict';

	var FrmQuizzesGradingScale = {
		init: function() {
			var self = this;
			$( 'body' ).on( 'click', '.grading-scale-row .frm_add_form_row', function( el ) {
				el.preventDefault();
				self.addRow();
			});

			$( 'body' ).on( 'click', '.grading-scale-row .frm_remove_form_row', function( el ) {
				el.preventDefault();
				self.removeRow( $( this ) );
			});
		},
		addRow: function() {
			var key = $( '.grading-scale-rows .grading-scale-row' ).length; //Starts at 1
			var newRowHtml = '<div class="grading-scale-row">';
			newRowHtml += '<input class="small-text grade" type="text" name="frm_quizzes_grading_scale[' + key + '][grade]" />';
			newRowHtml += '<input class="small-text start" type="text" name="frm_quizzes_grading_scale[' + key + '][start]" />';
			newRowHtml += '<input class="small-text end" type="text" name="frm_quizzes_grading_scale[' + key + '][end]" />';
			newRowHtml += '<a href="#" class="frm_add_form_row "  aria-label="Add"><i class="frm_icon_font frm_plus_icon"> </i></a>';
			newRowHtml += '<a href="#" class="frm_remove_form_row  " aria-label="Remove"><i class="frm_icon_font frm_minus_icon"> </i></a>';
			newRowHtml += '</div>';
			$( '.grading-scale-rows' ).append( newRowHtml );
		},
		removeRow: function( el ) {
			el.parent().remove();
			this.resetKeys();

		},
		resetKeys: function() {
			$( '.grading-scale-rows .grading-scale-row' ).each( function( index ) {
				$( this ).find( 'input.grade' ).attr( 'name', 'frm_quizzes_grading_scale[' + index + '][grade]' );
				$( this ).find( 'input.start' ).attr( 'name', 'frm_quizzes_grading_scale[' + index + '][start]' );
				$( this ).find( 'input.end' ).attr( 'name', 'frm_quizzes_grading_scale[' + index + '][end]' );
			});
		}
	};

	$( function() {
		FrmQuizzesGradingScale.init();
	});
}( jQuery ) );
