<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}
?>
<input type="hidden" id="<?php echo esc_attr( $html_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field['default_value'] ); ?>" />
<p class="howto frm_clear">
	<?php
	printf(
		// translators: %d: field id.
		esc_html__( 'You can display your quiz result using the [%d] shortcode in an email action or in a View.', 'formidable-quizzes' ),
		(int) $field['id']
	);
	?>
</p>
