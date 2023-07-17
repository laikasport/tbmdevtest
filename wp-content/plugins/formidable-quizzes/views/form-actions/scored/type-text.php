<?php
/**
 * Setting for text fields in scored quiz
 *
 * @package FrmQuizzes
 * @since 2.0.0
 *
 * @var object        $field       Field data.
 * @var WP_Post       $form_action Form action post object.
 * @var FrmFormAction $this        Form action object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$score_manually = FrmQuizzesAppHelper::field_is_manual( $field->id, $form_action );
$max_score      = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'max_score', $field->id, $score );
$method         = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'compare_method', $field->id, 'equal' );
?>
<p>
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[score_manually]"
			value="1"
			class="frm_quizzes_admin_score_manually"
			<?php checked( $score_manually, true ); ?>
		/>
		<?php esc_html_e( 'Score this field manually', 'formidable-quizzes' ); ?>
	</label>
</p>

<div class="frm_quizzes_admin_auto_score">
	<p class="description">
		<?php esc_html_e( 'Enter a comma-separated list of correct choices', 'formidable-quizzes' ); ?>
	</p>

	<div class="frm_quizzes_flex">
		<input
			type="text"
			value="<?php echo esc_attr( $score ); ?>"
			class="frm_quizzes_admin_score"
			name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[score]"
			placeholder="<?php esc_attr_e( 'Points', 'formidable-quizzes' ); ?>"
		/>

		<select
			name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[compare_method]"
			class="frm_quizzes_admin_compare_method"
		>
			<?php
			$methods = FrmQuizzesAppHelper::get_text_compare_methods();
			foreach ( $methods as $key => $value ) {
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $key ),
					selected( $key, $method, false ),
					esc_html( $value )
				);
			}
			?>
		</select>

		<?php
		$input_name = $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . '][corrects][]';
		?>
		<div class="frm_quizzes_admin_correct_texts">
			<div class="frm_quizzes_admin_tags_input">
			<span>
				<?php foreach ( $corrects as $correct ) : ?>
					<span>
						<?php echo esc_html( $correct ); ?>
						<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $correct ); ?>" />
					</span>
				<?php endforeach; ?>
			</span>

				<input
					type="text"
					placeholder="<?php esc_attr_e( 'Add correct answer(s)', 'formidable-quizzes' ); ?>"
					data-name="<?php echo esc_attr( $input_name ); ?>"
					name="<?php echo esc_attr( $input_name ); ?>"
				/>
			</div>
		</div>
	</div>
</div>

<div class="frm_quizzes_admin_manual_score">
	<p>
		<label for="frm_quizzes_max_score_<?php echo intval( $field->id ); ?>">
			<?php esc_html_e( 'Max score', 'formidable-quizzes' ); ?>
		</label>

		<input
			type="text"
			id="frm_quizzes_max_score_<?php echo intval( $field->id ); ?>"
			name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[max_score]"
			value="<?php echo esc_attr( $max_score ); ?>"
			style="max-width: 70px"
		/>
	</p>
</div>
