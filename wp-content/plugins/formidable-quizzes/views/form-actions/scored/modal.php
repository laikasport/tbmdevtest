<?php
/**
 * View for scored quiz settings
 *
 * @package FrmQuizzes
 * @since 2.0.0
 *
 * @var array         $fields      Fields array.
 * @var object        $form_action Form action post object.
 * @var FrmFormAction $this        Form action object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<div id="frm_quizzes_scored_settings_modal" class="frm_quizzes_modal frm-modal">
	<div class="frm_quizzes_modal__overlay ui-widget-overlay"></div>

	<div class="frm_quizzes_modal__container frm-dialog">
		<div class="metabox-holder">
			<div class="postbox">
				<?php FrmQuizzesFormActionHelper::modal_header( __( 'Scored Quiz', 'formidable-quizzes' ) ); ?>

				<div class="frm_quizzes_modal__content frm_modal_content">
					<div id="frm_quizzes_scored_settings">
						<?php
						foreach ( $fields as $index => $field ) {
							$field->index = $index;
							include FrmQuizzesAppController::path() . '/views/form-actions/scored/field.php';
							unset( $field );
						}
						?>
					</div>
				</div>

				<?php FrmQuizzesFormActionHelper::modal_footer(); ?>
			</div>
		</div>
	</div>
</div>
<?php
unset( $fields );
