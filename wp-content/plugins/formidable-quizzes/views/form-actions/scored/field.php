<?php
/**
 * View for field setting of scored quiz
 *
 * @package FrmQuizzes
 * @since 2.0
 *
 * @var object $field Field data.
 * @var object $form_action Form action post object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$scored_type = FrmQuizzesAppHelper::get_scored_type( $field );

$enabled = FrmQuizzesAppHelper::field_is_enabled( $field->id, $form_action );
$classes = 'frm_quizzes_scored_field frm_quizzes_scored_field--type_' . $scored_type;
if ( ! $enabled ) {
	$classes .= ' frm_quizzes_scored_field--disabled';
}

if ( 'choice' === $scored_type && FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'adv_scoring', $field->id ) ) {
	$classes .= ' frm_quizzes_scored_field--adv_scoring';
}

if ( 'text' === $scored_type && FrmQuizzesAppHelper::field_is_manual( $field->id, $form_action ) ) {
	$classes .= ' frm_quizzes_scored_field--score_manually';
}
?>
<div class="<?php echo esc_attr( $classes ); ?>">
	<input
		type="hidden"
		value="<?php echo esc_attr( $field->id ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'quiz' ) . '[' . esc_attr( $field->id ) . ']' ); ?>[id]"
	/>

	<?php
	$id_attr = $this->get_field_id( 'enable' . $field->id );
	FrmProHtmlHelper::toggle(
		$id_attr,
		$this->get_field_name( 'enable' ),
		array(
			'echo'      => true,
			'checked'   => $enabled,
			'div_class' => 'with_frm_style frm_toggle frm_quizzes_admin_enable_field',
			'on_label'  => $field->id,
		)
	);

	?>

	<div class="frm_quizzes_scored_field__content">
		<label for="<?php echo esc_attr( $id_attr ); ?>" class="frm_quizzes_scored_field__name">
			<?php
			echo esc_html( wp_strip_all_tags( $field->name ) );
			?>
		</label>

		<div class="frm_quizzes_scored_field__settings">
			<?php
			$score    = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'score', $field->id, 1 );
			$corrects = FrmQuizzesFormActionHelper::get_correct_values( $field->id, $form_action );

			include __DIR__ . "/type-{$scored_type}.php";
			?>
		</div>
	</div>
</div><!-- End .frm_quizzes_scored_fields_fields -->
