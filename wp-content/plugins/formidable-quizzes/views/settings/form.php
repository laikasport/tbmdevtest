<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}
?>
<h3 class="frm-no-border frm_no_top_margin">
	<?php esc_html_e( 'Grading Scale', 'formidable-quizzes' ); ?>
</h3>

<div class="grading-scale-row-labels">
	<?php // remove whitespace for consistent widths. ?>
	<label>
		<?php echo esc_html_e( 'Letter Grade', 'formidable-quizzes' ); ?></label><label>
		<?php echo esc_html_e( 'Start %', 'formidable-quizzes' ); ?></label><label>
		<?php echo esc_html_e( 'End %', 'formidable-quizzes' ); ?>
	</label>
</div>
<div class="grading-scale-rows">
	<?php foreach ( $quiz_settings->grading_scale as $key => $grade_scale ) : ?>
		<div class="grading-scale-row">
			<?php foreach ( array( 'grade', 'start', 'end' ) as $grade ) { ?>
				<input class="<?php echo esc_attr( $grade ); ?>" type="text" name="<?php echo esc_attr( 'frm_quizzes_grading_scale[' . esc_attr( $key ) . '][' . esc_attr( $grade ) . ']' ); ?>" value="<?php echo esc_attr( $grade_scale[ $grade ] ); ?>" />
			<?php } ?>
			<a href="#" class="frm_add_form_row" aria-label="Add"><i class="frm_icon_font frm_plus_icon"> </i></a>
			<?php if ( 0 != $key ) : ?>
				<a href="#" class="frm_remove_form_row" aria-label="Remove"><i class="frm_icon_font frm_minus_icon"> </i></a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
