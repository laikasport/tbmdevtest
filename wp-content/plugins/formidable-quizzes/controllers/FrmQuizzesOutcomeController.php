<?php
/**
 * Class FrmQuizzesOutcomeController
 *
 * @package FrmQuizzes
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmQuizzesOutcomeController {

	/**
	 * Weigh the outcomes for a form and return the outcome for a given entry id.
	 * The entry meta is important so it's better to call this function even if you already have an $entry object with missing meta.
	 *
	 * @param int $entry_id
	 * @return WP_Post|false False if no outcome was available for the given entry.
	 */
	public static function get_outcome( $entry_id ) {
		$entry = FrmEntry::getOne( $entry_id, true );
		if ( ! $entry ) {
			return false;
		}

		$outcomes = FrmQuizzesFormActionHelper::get_quiz_outcomes_for_form( $entry->form_id, true );
		if ( ! $outcomes ) {
			return false;
		}

		$outcome_helper = new FrmQuizzesOutcomeHelper( $outcomes, $entry );
		return $outcome_helper->get_outcome();
	}

	/**
	 * Gets form submission success message.
	 *
	 * @param string   $message  Success message.
	 * @param stdClass $form     Form object.
	 * @param mixed    $entry_id Entry ID.
	 * @return string
	 */
	public static function get_success_message( $message, $form, $entry_id ) {
		if ( ! $entry_id || ! is_numeric( $entry_id ) ) {
			return $message;
		}

		$quiz_field = FrmField::get_all_types_in_form( $form->id, 'quiz_score', 1 );
		if ( ! $quiz_field ) {
			return $message;
		}

		$outcome = self::get_outcome( (int) $entry_id );
		if ( ! ( $outcome instanceof WP_Post ) ) {
			return $message;
		}

		$field_object = FrmFieldFactory::get_field_object( $quiz_field );
		$message     .= $field_object->get_display_value( $outcome->ID );
		$message      = FrmFormsHelper::get_success_message(
			array(
				'message'  => $message,
				'form'     => $form,
				'entry_id' => $entry_id,
				'class'    => 'frm_quizzes_show_outcome',
			)
		);
		$message      = FrmAppHelper::kses( $message, 'all' );

		/**
		 * @since 3.0
		 *
		 * @param string $border_radius 10px by default.
		 * @param array  $args {
		 *     @type int     $form_id
		 *     @type WP_Post $outcome
		 * }
		 */
		$border_radius = apply_filters(
			'frm_quiz_outcome_image_border_radius',
			'10px',
			array(
				'form_id' => (int) $form->id,
				'outcome' => $outcome,
			)
		);

		if ( '10px' !== $border_radius ) {
			// Inject custom border radius CSS variable to message wrapper.
			$message = str_replace(
				'class="frm_quizzes_show_outcome"',
				'class="frm_quizzes_show_outcome" style="--frm-outcome-image-border-radius:' . esc_attr( $border_radius ) . ';"',
				$message
			);
		}

		FrmQuizzesAppHelper::maybe_inline_css( 'outcome' );

		return $message;
	}

	/**
	 * Prevent quiz outcome/score field from appearing in conditional logic options for Quiz Outcome actions.
	 *
	 * @param array<string> $exclude_fields
	 * @param array         $args {
	 *     @type string $type Action type.
	 *     @type int    $form_id
	 * }
	 * @return array<string>
	 */
	public static function hide_quiz_score_from_quiz_outcome_condition_logic_row( $exclude_fields, $args ) {
		if ( FrmQuizzesFormActionHelper::$outcome_action_name === $args['type'] ) {
			$exclude_fields[] = 'quiz_score';
		}
		return $exclude_fields;
	}
}
