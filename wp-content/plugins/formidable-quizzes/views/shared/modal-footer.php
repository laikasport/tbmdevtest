<?php
/**
 * Views for modal footer
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_quizzes_modal__footer frm_modal_footer">
	<button type="button" class="button-primary frm-button-primary" data-frm-modal-dismiss>
		<?php esc_html_e( 'Done', 'formidable-quizzes' ); ?>
	</button>
</div>
