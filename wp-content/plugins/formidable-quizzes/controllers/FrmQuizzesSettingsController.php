<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesSettingsController {

	/**
	 * Flag to check if score is calculated. Calculated entry ID will be added here.
	 *
	 * @since 2.0.02
	 * @since 3.0 This is an array of entry IDs.
	 *
	 * @var array
	 */
	private static $is_calculated = array();

	/**
	 * Registers actions.
	 *
	 * @param array $actions Action classes.
	 * @return array
	 */
	public static function register_actions( $actions ) {
		$actions[ FrmQuizzesFormActionHelper::$action_name ]         = 'FrmQuizzesAction';
		$actions[ FrmQuizzesFormActionHelper::$outcome_action_name ] = 'FrmQuizzesOutcomeAction';

		return $actions;
	}

	/**
	 * Update the score when the form action is triggered.
	 *
	 * @since 2.0
	 * @since 2.0.02 Support entry ID and form ID.
	 *
	 * @param WP_Post    $form_action The quiz form action.
	 * @param object|int $entry       The entry object or entry ID getting created/updated.
	 * @param object|int $form        The form object or form ID that includes the entry.
	 *
	 * @return void
	 */
	public static function calculate_quiz_score( $form_action, $entry, $form ) {
		FrmEntry::maybe_get_entry( $entry );
		if ( ! $entry ) {
			return;
		}

		if ( in_array( intval( $entry->id ), self::$is_calculated, true ) ) {
			return;
		}

		FrmForm::maybe_get_form( $form );
		if ( ! $form ) {
			return;
		}

		if ( isset( $form_action->post_content['quiz_type'] ) && 'outcome' === $form_action->post_content['quiz_type'] ) {
			self::maybe_set_outcome_to_item_meta( $entry );
		} else {
			self::maybe_set_score_to_item_meta( $entry, $form_action );
		}

		self::$is_calculated[] = intval( $entry->id );
	}

	/**
	 * Make sure that item meta includes quiz outcome if the form has outcomes.
	 *
	 * @param stdClass $entry The entry object or entry ID getting created/updated.
	 * @return void
	 */
	private static function maybe_set_outcome_to_item_meta( $entry ) {
		$quiz_field = FrmField::get_all_types_in_form( $entry->form_id, 'quiz_score', 1 );
		if ( ! $quiz_field ) {
			return;
		}

		$outcome_action = FrmQuizzesOutcomeController::get_outcome( (int) $entry->id );
		if ( ! ( $outcome_action instanceof WP_Post ) ) {
			return;
		}

		$outcome_id                      = $outcome_action->ID;
		$entry->metas[ $quiz_field->id ] = $outcome_id;

		FrmQuizzesFormActionHelper::sync_quiz_score_entry_meta( $entry->id, $quiz_field, $outcome_id );
	}

	/**
	 * Make sure that item meta includes quiz score if the form has a scored quiz action.
	 *
	 * @param stdClass $entry The entry object or entry ID getting created/updated.
	 * @param WP_Post  $form_action
	 *
	 * @return void
	 */
	private static function maybe_set_score_to_item_meta( $entry, $form_action ) {
		$entry_id = $entry->id;
		$form_id  = $entry->form_id;
		$scoring  = new FrmQuizzes( compact( 'form_id', 'entry', 'entry_id', 'form_action' ) );
		$score    = $scoring->calculate_score();
		if ( false !== $score && $scoring->score_field_id ) {
			// Update metas in entry variable. This fixes the score not displaying in email.
			$entry->metas[ $scoring->score_field_id ] = $score;
		}
	}

	/**
	 * Calculates score when entry is created.
	 *
	 * @since 2.0.02
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 *
	 * @return void
	 */
	public static function calculate_score_when_create_entry( $entry_id, $form_id ) {
		self::calculate_score_when_entry_changed( $entry_id, $form_id );
	}

	/**
	 * Calculates score when entry is updated.
	 *
	 * @since 2.0.02
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 *
	 * @return void
	 */
	public static function calculate_score_when_update_entry( $entry_id, $form_id ) {
		self::calculate_score_when_entry_changed( $entry_id, $form_id, 'update' );
	}

	/**
	 * Calculates score when entry is created or updated.
	 *
	 * @since 2.0.02
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $form_id  Form ID.
	 * @param string $event    Event name. Accepts `create` or `update`.
	 *
	 * @return void
	 */
	private static function calculate_score_when_entry_changed( $entry_id, $form_id, $event = 'create' ) {
		$quiz_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $form_id, true );

		if ( $quiz_action ) {
			if ( ! self::is_event_enabled( $event, $quiz_action ) ) {
				return;
			}
		} else {
			$outcomes = FrmQuizzesFormActionHelper::get_quiz_outcomes_for_form( $form_id, true );
			if ( ! $outcomes ) {
				return;
			}

			$quiz_action = reset( $outcomes );
		}

		self::calculate_quiz_score( $quiz_action, $entry_id, $form_id );
	}

	/**
	 * Checks if the given event is enabled in the Quiz action.
	 *
	 * @since 2.0.02
	 *
	 * @param string $event       Event name.
	 * @param object $quiz_action Quiz action.
	 * @return bool
	 */
	private static function is_event_enabled( $event, $quiz_action ) {
		if ( ! isset( $quiz_action->post_content['event'] ) || ! is_array( $quiz_action->post_content['event'] ) ) {
			return false;
		}
		return in_array( $event, $quiz_action->post_content['event'], true );
	}

	/**
	 * @param array $sections
	 * @return array
	 */
	public static function add_settings_section( $sections ) {
		$sections['quizzes'] = array(
			'class'    => 'FrmQuizzesSettingsController',
			'function' => 'route',
			'icon'     => 'frm_icon_font frm_percent_icon frm_quiz_icon',
			'name'     => __( 'Quizzes', 'formidable-quizzes' ),
		);
		return $sections;
	}

	/**
	 * @return void
	 */
	public static function display_form() {
		$settings = new FrmQuizzesSettings();
		$quiz_settings = $settings->settings;

		require_once FrmQuizzesAppController::path() . '/views/settings/form.php';
	}

	/**
	 * @return void
	 */
	public static function process_form() {
		$settings = new FrmQuizzesSettings();
		$process_form = FrmAppHelper::get_post_param( 'process_form', '', 'sanitize_text_field' );

		if ( wp_verify_nonce( $process_form, 'process_form_nonce' ) ) {
			$settings->update( $_POST );
			$settings->store();
			$message = __( 'Settings Saved', 'formidable' );
		}

		$quiz_settings = $settings->settings;

		require_once FrmQuizzesAppController::path() . '/views/settings/form.php';
	}

	/**
	 * @return void
	 */
	public static function route() {
		$action = FrmAppHelper::get_param( 'action' );
		if ( 'process-form' == $action ) {
			self::process_form();
			return;
		}
		self::display_form();
	}

}
