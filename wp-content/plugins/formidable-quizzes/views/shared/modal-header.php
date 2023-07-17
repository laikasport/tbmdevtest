<?php
/**
 * View for modal header
 *
 * @package FrmQuizzes
 * @since 2.0.0
 *
 * @var string $modal_title Modal title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_quizzes_modal__header frm_modal_top">
	<div class="frm-modal-title"><?php echo esc_html( $modal_title ); ?></div>

	<div>
		<a href="#" data-frm-modal-dismiss class="dismiss" title="<?php esc_attr_e( 'Cancel', 'formidable-quizzes' ); ?>">
			<?php esc_html_e( 'Cancel', 'formidable-quizzes' ); ?>
		</a>
	</div>
</div>
