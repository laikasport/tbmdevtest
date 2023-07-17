<?php
/**
 * Quizzes app helper
 *
 * @package FrmQuizzes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmQuizzesAppHelper
 */
class FrmQuizzesAppHelper {

	/**
	 * Gets quiz types.
	 *
	 * @return array
	 */
	public static function get_quiz_types() {
		$quiz_types = array(
			'scored'  => __( 'Scored quiz', 'formidable-quizzes' ),
			'outcome' => __( 'Outcome quiz', 'formidable-quizzes' ),
		);

		/**
		 * Allows adding new quiz types.
		 *
		 * @since 2.0.0
		 *
		 * @param array $quiz_types Quiz types.
		 */
		return apply_filters( 'frm_quizzes_quiz_types', $quiz_types );
	}

	/**
	 * Gets field types excluded from scoring.
	 *
	 * @return string[]
	 */
	public static function get_excluded_field_types() {
		$no_save_fields = FrmField::no_save_fields();
		return array_merge( $no_save_fields, array( 'user_id', 'rte', 'quiz_score', 'file', 'signature', 'hidden' ) );
	}

	/**
	 * Checks if the given field is choice field.
	 *
	 * @param object $field Field object.
	 * @return bool
	 */
	public static function is_choice_field( $field ) {
		return in_array( $field->type, self::get_choice_field_types(), true );
	}

	/**
	 * Gets choice field types.
	 *
	 * @return array
	 */
	protected static function get_choice_field_types() {
		/**
		 * Allows modifying choice field types.
		 *
		 * @since 2.0
		 *
		 * @param array $field_types Field types.
		 */
		return apply_filters( 'frm_quizzes_choice_field_types', array( 'radio', 'checkbox', 'select' ) );
	}

	/**
	 * Checks if field is enabled for quiz.
	 *
	 * @param int    $field_id    Field ID.
	 * @param object $quiz_action Quiz action.
	 * @return bool
	 */
	public static function field_is_enabled( $field_id, $quiz_action ) {
		$enabled = FrmQuizzesFormActionHelper::get_setting_value( $quiz_action, 'enable' );

		// The format might be different after import.
		$enabled = array_map( 'intval', $enabled );
		return is_array( $enabled ) && in_array( (int) $field_id, $enabled, true );
	}

	/**
	 * Checks if field is scored manually.
	 *
	 * @param int    $field_id    Field ID.
	 * @param object $quiz_action Quiz action.
	 * @return bool
	 */
	public static function field_is_manual( $field_id, $quiz_action ) {
		$manual = FrmQuizzesFormActionHelper::get_setting_value( $quiz_action, 'score_manually', $field_id );
		return ! empty( $manual );
	}

	/**
	 * Gets message when score is not set.
	 *
	 * @return string
	 */
	public static function get_not_scored_message() {
		return __( 'Unscored', 'formidable-quizzes' );
	}

	/**
	 * Gets quiz scored type.
	 *
	 * @param stdClass $field Field data.
	 * @return string
	 */
	public static function get_scored_type( $field ) {
		$type = 'text';

		if ( self::is_choice_field( $field ) ) {
			$type = 'choice';
		}

		/**
		 * Allows modifying the quiz scored type.
		 *
		 * @since 2.0.0
		 *
		 * @param string $type  Scored type.
		 * @param object $field Field data.
		 */
		return apply_filters( 'frm_quizzes_scored_type', $type, $field );
	}

	/**
	 * Gets text answer compare methods.
	 *
	 * @return array
	 */
	public static function get_text_compare_methods() {
		return array(
			'equal'       => __( 'Should equal', 'formidable-quizzes' ),
			'contain_all' => __( 'Should contain all', 'formidable-quizzes' ),
			'contain_one' => __( 'Should contain one', 'formidable-quizzes' ),
			'not_contain' => __( 'Should not contain', 'formidable-quizzes' ),
		);
	}

	/**
	 * Check if on the view entry page in the admin area.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public static function is_admin_view() {
		return FrmAppHelper::is_admin_page( 'formidable-entries' ) && 'show' === FrmAppHelper::get_param( 'frm_action' );
	}

	/**
	 * Maybe print inline CSS required for a scored quiz result or a styled outcome.
	 * It needs to use a <style> tag for an AJAX request, otherwise it won't load.
	 * If it is not an AJAX request, enqueue it normally.
	 *
	 * @since 3.0
	 *
	 * @param string $filename_partial /css/frm-quizzes-$filename_partial either 'outcome' or 'result' for scored quizzes.
	 * @return void
	 */
	public static function maybe_inline_css( $filename_partial ) {
		$relative_filepath = '/css/frm-quizzes-' . $filename_partial . '.css';

		if ( wp_doing_ajax() ) {
			?>
			<style>
				<?php readfile( FrmQuizzesAppController::path() . $relative_filepath ); ?>
			</style>
			<?php
			return;
		}

		wp_enqueue_style( 'frm-quizzes-' . $filename_partial, FrmQuizzesAppController::plugin_url() . $relative_filepath, array(), FrmQuizzesAppController::$plug_version );
	}
}
