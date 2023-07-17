<?php
/**
 * Setting for choice fields in scored quiz
 *
 * @package FrmQuizzes
 * @since 2.0.0
 *
 * @var float         $score       Score for field.
 * @var array         $corrects    Correct answers.
 * @var object        $field       Field data.
 * @var WP_Post       $form_action Form action post object.
 * @var FrmFormAction $this        Form action object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$adv_scoring = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'adv_scoring', $field->id );
$scores      = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'scores', $field->id, array() );
$answers     = FrmQuizzesFormActionHelper::get_all_choice_answers_from_field( $field );
$image_class = FrmQuizzesFormActionHelper::get_class_for_choice_answer_setting( $field );
?>
<p>
	<label>
		<input
			type="checkbox"
			value="1"
			class="frm_quizzes_admin_adv_scoring"
			name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[adv_scoring]"
			<?php checked( $adv_scoring ); ?>
		/>
		<?php esc_html_e( 'Advanced scoring', 'formidable-quizzes' ); ?>
	</label>
</p>

<div class="frm_quizzes_admin_adv_scores <?php echo esc_attr( $image_class ); ?>">
	<ul>
		<?php
		foreach ( $answers as $answer ) :
			if ( FrmQuizzesFormActionHelper::is_empty_answer( $answer['value'] ) ) {
				continue;
			}
			$value = isset( $scores[ $answer['value'] ] ) ? $scores[ $answer['value'] ] : 0;
			?>
			<li>
				<input type="text" value="<?php echo floatval( $value ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . '][scores]' . "[{$answer['value']}]" ); ?>" />

				<?php FrmQuizzesFormActionHelper::maybe_show_choice_answer_image( $answer, $image_class ); ?>

				<?php echo esc_html( $answer['value'] ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

<div class="frm_quizzes_admin_normal_score frm_quizzes_flex <?php echo esc_attr( $image_class ); ?>">
	<input
		type="text"
		value="<?php echo esc_attr( $score ); ?>"
		class="frm_quizzes_admin_score"
		name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[score]"
		placeholder="<?php esc_attr_e( 'Points', 'formidable-quizzes' ); ?>"
	/>

	<div class="frm_quizzes_multi_checkboxes frm_quizzes_admin_correct_choices">
		<button type="button">
			<?php
			foreach ( $corrects as $answer ) {
				echo '<span>' . esc_html( $answer ) . '</span>';
			}
			?>

			<span class="frm_quizzes_placeholder"><?php esc_html_e( 'Add correct answer(s)', 'formidable-quizzes' ); ?></span>
		</button>

		<ul class="multiselect-container frm-dropdown-menu">
			<?php
			foreach ( $answers as $answer ) :
				if ( FrmQuizzesFormActionHelper::is_empty_answer( $answer['value'] ) ) {
					continue;
				}
				?>
				<li class="multiselect-option dropdown-item">
					<label>
						<input
							type="checkbox"
							value="<?php echo esc_attr( $answer['value'] ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[corrects][]"
							<?php checked( in_array( $answer['value'], $corrects, true ) ); ?>
						/>

						<?php FrmQuizzesFormActionHelper::maybe_show_choice_answer_image( $answer, $image_class ); ?>

						<span><?php echo esc_html( $answer['label'] ); ?></span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
