<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzes {

	protected $form_id = 0;

	protected $entry_id = 0;

	/**
	 * If on v2.0 or not.
	 *
	 * @var int
	 *
	 * @since 2.0
	 */
	protected $version;

	/**
	 * Entry data.
	 *
	 * @var object
	 */
	protected $entry;

	/**
	 * Form action data.
	 *
	 * @var object
	 *
	 * @since 2.0
	 */
	protected $form_action;

	/**
	 * The field getting graded.
	 *
	 * @var object
	 *
	 * @since 2.0
	 */
	protected $field;

	/**
	 * Score field ID.
	 *
	 * @var int
	 */
	public $score_field_id = 0;

	public function __construct( $atts = array() ) {
		if ( ! empty( $atts['form_id'] ) ) {
			$this->form_id  = $atts['form_id'];
		}

		if ( ! empty( $atts['entry_id'] ) ) {
			$this->entry_id = $atts['entry_id'];
		}

		if ( ! empty( $atts['entry'] ) ) {
			$this->entry = $atts['entry'];
			if ( empty( $this->entry_id ) ) {
				$this->entry_id = $this->entry->id;
			}
		}

		if ( ! empty( $atts['form_action'] ) ) {
			$this->form_action = $atts['form_action'];
		}
	}

	/**
	 * Gets the entry data.
	 *
	 * @since 2.0
	 *
	 * @return object
	 */
	protected function get_entry() {
		if ( ! $this->entry ) {
			$this->entry = FrmEntry::getOne( $this->entry_id, true );
		}

		return $this->entry;
	}

	/**
	 * Gets the form ID.
	 *
	 * @since 2.0
	 *
	 * @return int
	 */
	protected function get_form_id() {
		if ( ! $this->form_id ) {
			$entry = $this->get_entry();
			if ( ! empty( $entry->form_id ) ) {
				$this->form_id = $entry->form_id;
			}
		}

		return $this->form_id;
	}

	/**
	 * Get the version we should be using.
	 *
	 * @since 2.0
	 *
	 * @return int
	 */
	protected function get_version() {
		if ( empty( $this->version ) ) {
			$is_migrated = FrmQuizzesMigrationController::migrated_to_v2();
			$this->version = $is_migrated ? 2 : 1;
		}

		return $this->version;
	}

	/**
	 * Gets form action data.
	 *
	 * @since 2.0
	 *
	 * @return object|null
	 */
	protected function get_form_action() {
		if ( ! $this->form_action ) {
			if ( $this->form_id ) {
				$this->form_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $this->form_id, true );
			} else {
				$this->form_action = FrmQuizzesFormActionHelper::get_quiz_action_from_entry_id( $this->entry_id, true );
			}
		}

		return $this->form_action;
	}

	/**
	 * Get the number of questions in the key that have an answer.
	 *
	 * @return float
	 */
	public function get_question_count() {
		if ( $this->get_version() === 1 ) {
			return $this->get_question_count_old();
		}

		return $this->get_max_score();
	}

	/**
	 * Find the scoring settings for a single field.
	 *
	 * @param int $field_id
	 *
	 * @return array
	 */
	public function get_scoring_for_field( $field_id ) {
		$form_action = $this->get_form_action();
		$settings    = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'quiz' );

		foreach ( (array) $settings as $field_setting ) {
			$field_setting = (array) $field_setting;
			if ( ! empty( $field_setting['id'] ) && $field_setting['id'] === $field_id ) {
				return $field_setting;
			}
		}
		return array();
	}

	/**
	 * Find a scoring setting for a single field.
	 *
	 * @param int    $field_id The field to check.
	 * @param string $name     The name of the saved setting.
	 *
	 * @return mixed
	 */
	public function get_scoring_detail_for_field( $field_id, $name ) {
		$field_settings = $this->get_scoring_for_field( $field_id );
		return isset( $field_settings[ $name ] ) ? $field_settings[ $name ] : false;
	}

	/**
	 * Find the scoring settings for a single field and return the max score.
	 *
	 * @param int $field_id
	 *
	 * @return float
	 */
	public function get_field_max_from_settings( $field_id ) {
		$field_settings = $this->get_scoring_for_field( $field_id );
		return $this->get_max_for_field( $field_settings );
	}

	/**
	 * Calculates the max score and add to the form action settings.
	 *
	 * @return float
	 */
	protected function get_max_score() {
		$form_action = $this->get_form_action();
		if ( ! $form_action ) {
			return 0;
		}

		$settings = FrmQuizzesFormActionHelper::get_setting_value( $form_action, 'quiz' );

		$max_score = 0;
		foreach ( (array) $settings as $field_setting ) {
			$field_setting = (array) $field_setting;
			if ( ! empty( $field_setting['id'] ) && in_array( $field_setting['id'], $form_action->post_content['enable'], true ) ) {
				$max_score += $this->get_max_for_field( $field_setting );
			}
		}

		return $max_score;
	}

	/**
	 * Get the max score for a single field.
	 *
	 * @param array $field_setting The quiz settings for this field.
	 *
	 * @return float
	 */
	protected function get_max_for_field( $field_setting ) {
		$manual_score = ! empty( $field_setting['score_manually'] );
		if ( $manual_score ) {
			return $this->get_score_setting( $field_setting, 'max_score' );
		}

		$advanced_score = ! empty( $field_setting['adv_scoring'] ) && ! empty( $field_setting['scores'] ) && is_array( $field_setting['scores'] );
		if ( ! $advanced_score ) {
			return $this->get_score_setting( $field_setting, 'score' );
		}

		// This is a choice question with adv scoring.
		$field = FrmField::getOne( $field_setting['id'] );
		if ( ! $field ) {
			return 0;
		}

		$multiple_options = FrmField::is_field_with_multiple_values( $field );
		if ( $multiple_options ) {
			$max_score = $this->get_total_for_all( $field_setting );
		} else {
			$max_score = $this->get_single_max( $field_setting );
		}

		return $max_score;
	}

	/**
	 * Get a score value from the quiz settings.
	 *
	 * @param array  $field_setting The quiz settings for this field.
	 * @param string $key           The name of a setting: score, max_score.
	 * @return float
	 */
	protected function get_score_setting( $field_setting, $key = 'score' ) {
		return ( isset( $field_setting[ $key ] ) && $field_setting[ $key ] > 0 ) ? floatval( $field_setting[ $key ] ) : 1;
	}

	/**
	 * Get the max score for a multiselect or checkbox field.
	 *
	 * @param array $field_setting The quiz settings for this field.
	 */
	protected function get_total_for_all( $field_setting ) {
		$max_score = 0;
		foreach ( $field_setting['scores'] as $score ) {
			if ( $score > 0 ) {
				$max_score += floatval( $score );
			}
		}

		return $max_score;
	}

	/**
	 * Get the highest score for radio options.
	 *
	 * @param array $field_setting The quiz settings for this field.
	 */
	protected function get_single_max( $field_setting ) {
		return max( $field_setting['scores'] );
	}

	private function get_quiz_field() {
		return FrmField::get_all_types_in_form( $this->get_form_id(), 'quiz_score', 1 );
	}

	/**
	 * Calculates score and saves it.
	 *
	 * @since 2.0 Added `$return_val` paremeter.
	 *
	 * @return float|false
	 */
	public function calculate_score() {
		if ( $this->get_version() === 1 ) {
			$score = $this->calculate_entry_key_score();
		} else {
			$score = $this->calculate_action_score();
		}

		if ( $score !== false ) {
			$this->save_score( $score );
		}

		return $score;
	}

	/**
	 * Score using the form action settings.
	 *
	 * @since 2.0
	 *
	 * @return float|false Return `false` if quiz action is not found or manual score is not set.
	 */
	private function calculate_action_score() {
		$quiz_action = $this->get_form_action();
		if ( ! $quiz_action ) {
			// Don't score if there's no form action enabled.
			return false;
		}

		$removed_likert_filter = $this->maybe_remove_likert_filter();

		$entry_values = new FrmProEntryValues( $this->entry_id );
		$total_score  = 0;
		foreach ( $entry_values->get_field_values() as $field_value ) {
			if ( ! FrmQuizzesAppHelper::field_is_enabled( $field_value->get_field_id(), $quiz_action ) ) {
				continue; // Field is not enabled for quiz.
			}

			$check_result = $this->check_field( $field_value );
			if ( ! isset( $check_result['correct'] ) || is_null( $check_result['correct'] ) ) {
				// Manual scoring is enabled but it's not set.
				return false;
			}

			if ( $check_result['correct'] ) {
				$total_score += $check_result['score'];
			}

			unset( $field_value );
		}

		$this->maybe_restore_likert_filter( $removed_likert_filter );

		return $total_score;
	}

	/**
	 * Temporarily remove filter that removes radio and checkbox from get_field_values array so we can score them.
	 *
	 * @since 3.1
	 *
	 * @return bool True if the filter is removed.
	 */
	private function maybe_remove_likert_filter() {
		$filter_name = $this->get_likert_filter_name();
		$filter      = $this->get_likert_filter_function();
		$has_filter  = has_filter( $filter_name, $filter );

		if ( $has_filter ) {
			remove_filter( $filter_name, $filter );
		}

		return $has_filter;
	}

	/**
	 * If the Likert filter was removed, restore it at the end of the function.
	 *
	 * @since 3.1
	 *
	 * @param bool $removed_likert_filter
	 * @return void
	 */
	private function maybe_restore_likert_filter( $removed_likert_filter ) {
		if ( ! $removed_likert_filter ) {
			return;
		}
		add_filter( $this->get_likert_filter_name(), $this->get_likert_filter_function() );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	private function get_likert_filter_name() {
		return 'frm_entry_values_fields';
	}

	/**
	 * @since 3.1
	 *
	 * @return array
	 */
	private function get_likert_filter_function() {
		return array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' );
	}

	/**
	 * Gets check data for a field.
	 *
	 * @since 2.0
	 *
	 * @param FrmFieldValue $field_value Field value object.
	 *
	 * @return array See {@see FrmQuizzes::get_default_field_check_result()}.
	 */
	public function check_field( $field_value ) {
		$this->field  = $field_value->get_field();
		$this->entry  = $field_value->get_entry();

		$scored_type  = FrmQuizzesAppHelper::get_scored_type( $this->field );
		$check_method = array( $this, 'check_field_' . $scored_type );
		if ( is_callable( $check_method ) ) {
			$result = call_user_func_array( $check_method, array( $field_value ) );
		} else {
			$result = $this->get_default_field_check_result();
		}

		$result = (array) $result;

		$field_id          = $this->field->id;
		$result['max']     = $this->get_field_max_from_settings( $field_id );
		$result['compare'] = $this->get_scoring_detail_for_field( $field_id, 'compare_method' );
		$result['compare'] = $result['compare'] === false ? 'equal' : $result['compare'];

		$is_adv_scoring    = 'choice' === $scored_type && $this->is_adv_scoring_enabled();
		$is_manually_score = 'text' === $scored_type && $this->is_manually_scoring_enabled();
		if ( is_numeric( $result['score'] ) && $result['max'] > $result['score'] && ! $is_adv_scoring && ! $is_manually_score ) {
			$result['correct'] = false;
		}

		$form_action = $this->get_form_action();

		/**
		 * Allows making question is correct or incorrect via custom code.
		 *
		 * @param bool  $is_correct Is `true` if question is correct.
		 * @param array $args       {
		 *     The arguments.
		 *
		 *     @type object $field         Field data.
		 *     @type mixed  $value         The answer value.
		 *     @type object $entry         Entry data.
		 *     @type array  $saved_answers List of correct answers.
		 * }
		 */
		$result['correct'] = apply_filters(
			'frm_quiz_is_correct',
			$result['correct'],
			array(
				'field'         => $this->field,
				'value'         => $this->get_value_for_checking_from_field_value( $field_value ),
				'entry'         => $this->entry,
				'saved_answers' => FrmQuizzesFormActionHelper::get_correct_values( $field_id, $form_action ),
			)
		);

		/**
		 * Allows modifying the question check result.
		 *
		 * @since 2.0
		 *
		 * @param array $result The question check result.
		 * @param array $args {
		 *     The arguments.
		 *
		 *     @type FrmFieldValue $field_value Field value object.
		 *     @type object        $form_action Quiz action object.
		 * }
		 */
		return apply_filters( 'frm_quizzes_check_result', $result, compact( 'field_value', 'form_action' ) );
	}

	/**
	 * Checks the choice question
	 *
	 * @since 2.0
	 * @param FrmFieldValue $field_value Field value object.
	 *
	 * @return array
	 */
	protected function check_field_choice( $field_value ) {
		$field = $this->field;
		$value = (array) $this->get_value_for_checking_from_field_value( $field_value );

		if ( $this->is_adv_scoring_enabled() ) {
			return $this->check_field_choice_with_adv_scoring( $value );
		}

		$quiz_action  = $this->get_form_action();
		$corrects_raw = FrmQuizzesFormActionHelper::get_correct_values( $field->id, $quiz_action );
		$corrects     = array_map( array( $this, 'preprocess_check_value' ), $corrects_raw );
		$result       = $this->get_default_field_check_result( count( $corrects ) === count( $value ) );

		$result['corrects_raw'] = $corrects_raw;
		$result['corrects']     = $corrects;

		// User must choose all correct answers.
		foreach ( $corrects as $correct ) {
			if ( ! in_array( $correct, $value, true ) ) {
				$result['correct'] = false;
			} else {
				$result['correct_answers'][] = $correct;
			}
		}

		if ( $result['correct'] ) {
			$result['score'] = FrmQuizzesFormActionHelper::get_setting_value( $quiz_action, 'score', $field->id, 0 );
		}

		return $result;
	}


	/**
	 * Checks if Advanced scoring is enabled.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	private function is_adv_scoring_enabled() {
		return (bool) FrmQuizzesFormActionHelper::get_setting_value( $this->get_form_action(), 'adv_scoring', $this->field->id );
	}

	/**
	 * Checks if Manually scoring is enabled.
	 *
	 * @since 3.1
	 *
	 * @return bool
	 */
	private function is_manually_scoring_enabled() {
		return FrmQuizzesAppHelper::field_is_manual( $this->field->id, $this->get_form_action() );
	}

	/**
	 * Checks the choice question with advanced scoring enabled.
	 *
	 * @since 2.0
	 *
	 * @param array $value Answer value.
	 * @return array
	 */
	protected function check_field_choice_with_adv_scoring( $value ) {
		$quiz_action = $this->get_form_action();
		if ( ! $quiz_action ) {
			return $value;
		}

		$scores = FrmQuizzesFormActionHelper::get_setting_value( $quiz_action, 'scores', $this->field->id, array() );
		$scores = $this->preprocess_adv_scores( $scores );
		$result = $this->get_default_field_check_result( true );

		// Get corrects data to use later.
		foreach ( $scores as $key => $score ) {
			if ( $score['score'] > 0 ) {
				$result['corrects_raw'][] = $score['label'];
				$result['corrects'][]     = $key;
			}
		}

		foreach ( $value as $val ) {
			if ( isset( $scores[ $val ] ) ) {
				$score = $scores[ $val ]['score'];
				if ( $score > 0 ) {
					$result['correct_answers'][] = $val;
				}
				$result['score'] += $score;
			}
		}

		$negative_score = ! empty( $quiz_action->post_content['negative_score'] );
		if ( ! $negative_score && $result['score'] < 0 ) {
			$result['score'] = 0;
		}

		return $result;
	}

	/**
	 * Converts ["Answer" => 1] to ["answer" => ["label" => "Answer", "score" => 1]].
	 *
	 * @since 2.0
	 * @param array $scores Adv scores data.
	 * @return array
	 */
	protected function preprocess_adv_scores( $scores ) {
		$new_scores = array();
		foreach ( $scores as $key => $score ) {
			$new_scores[ $this->preprocess_check_value( $key ) ] = array(
				'label' => $key,
				'score' => $score,
			);
		}

		return $new_scores;
	}

	/**
	 * Checks the text question.
	 *
	 * @since 2.0
	 * @param FrmFieldValue $field_value Field value object.
	 *
	 * @return array
	 */
	protected function check_field_text( $field_value ) {
		$field_id       = $this->field->id;
		$quiz_action    = $this->get_form_action();
		$score_manually = FrmQuizzesAppHelper::field_is_manual( $field_id, $quiz_action );

		if ( $score_manually ) {
			return $this->check_field_text_manually_scoring( $field_value );
		}

		$corrects_raw = FrmQuizzesFormActionHelper::get_correct_values( $field_id, $quiz_action );
		$field        = $this->field;
		$corrects     = array_map( array( $this, 'preprocess_check_value' ), $corrects_raw, compact( 'field' ) );
		$compare      = FrmQuizzesFormActionHelper::get_setting_value( $quiz_action, 'compare_method', $field_id, 'equal' );
		$result       = $this->get_default_field_check_result();
		$value        = $this->get_value_for_checking_from_field_value( $field_value );

		$check_method = array( $this, 'check_text_answer_' . $compare );
		if ( is_callable( $check_method ) ) {
			$result['correct'] = call_user_func_array( $check_method, array( $value, $corrects ) );
		}

		if ( $result['correct'] ) {
			$result['score'] = (float) FrmQuizzesFormActionHelper::get_setting_value( $quiz_action, 'score', $field_id, 0 );
		}

		$result['corrects_raw'] = $corrects_raw;
		$result['corrects']     = $corrects;

		return $result;
	}

	/**
	 * Checks the text question with manually scoring enabled.
	 *
	 * @since 3.1
	 *
	 * @param FrmFieldValue $field_value Field value object.
	 * @return array
	 */
	protected function check_field_text_manually_scoring( $field_value ) {
		$result = $this->get_default_field_check_result( null );
		$result = FrmQuizzesManualHelper::check_manually_scoring_field( $field_value, $result );
		if ( false !== $result['score'] ) {
			$result['correct'] = $result['score'] > 0;
		}

		return $result;
	}

	/**
	 * Gets value for checking from field value object.
	 *
	 * @since 2.0
	 * @param FrmFieldValue $field_value Field value object.
	 *
	 * @return mixed|null
	 */
	protected function get_value_for_checking_from_field_value( $field_value ) {
		$value     = $field_value->get_saved_value();
		$field_obj = FrmFieldFactory::get_field_object( $this->field );
		if ( $field_obj->is_combo_field ) {
			$value = $field_obj->get_display_value( $value );
		}

		if ( is_array( $value ) ) {
			$value = array_map( array( $this, 'preprocess_check_value' ), $value );
		} else {
			$value = $this->preprocess_check_value( $value );
		}

		return $value;
	}

	/**
	 * Pre-processes the check value.
	 *
	 * @since 2.0
	 * @param string $value Value string.
	 * @param object $field The field obj.
	 *
	 * @return string
	 */
	protected function preprocess_check_value( $value, $field = null ) {
		if ( $field ) {
			$check_method = array( $this, 'preprocess_' . $field->type . '_value' );
			if ( is_callable( $check_method ) ) {
				$value = call_user_func_array( $check_method, array( $value ) );
			}
		}
		return trim( strtolower( $value ) );
	}

	/**
	 * Convert the correct answer to the Y-m-d format so we can compare.
	 *
	 * @since 2.0
	 * @param string $value The raw correct value.
	 *
	 * @return string
	 */
	protected function preprocess_date_value( $value ) {
		$new_value = FrmProAppHelper::maybe_convert_to_db_date( $value );
		if ( empty( $new_value ) ) {
			$value = gmdate( 'Y-m-d', strtotime( $value ) );
		}

		return $new_value ? $new_value : $value;
	}

	/**
	 * Convert the correct answer to the 13:00 format so we can compare.
	 *
	 * @since 2.0
	 * @param string $value The raw correct value.
	 *
	 * @return string
	 */
	protected function preprocess_time_value( $value ) {
		$new_value = FrmProAppHelper::format_time( $value, 'H:i' );
		if ( $new_value ) {
			$value = $new_value;
		}

		return $new_value ? $new_value : $value;
	}

	/**
	 * Gets the default field check result.
	 *
	 * @since 2.0
	 * @param bool|null $correct Default correct value.
	 * @return array {
	 *     The result array contains below data.
	 *
	 *     @type bool|null $correct      This field is counted as correct or not. Null means it hasn't been scored yet.
	 *     @type float     $score        The score to be added to the final score.
	 *     @type array     $corrects_raw Correct values in the quiz settings.
	 *     @type array     $corrects     Correct values after converting to check values.
	 * }
	 */
	protected function get_default_field_check_result( $correct = false ) {
		return array(
			'correct'         => $correct,
			'score'           => 0,
			'corrects_raw'    => array(),
			'corrects'        => array(),
			'correct_answers' => array(),
			'max'             => 1,
			'compare'         => 'equal',
		);
	}

	/**
	 * Compare the correct and actual answers.
	 *
	 * @since 2.0
	 * @param array|string $value    The user answer.
	 * @param array        $corrects The correct answers.
	 *
	 * @return bool
	 */
	protected function check_text_answer_equal( $value, $corrects ) {
		if ( ! is_array( $value ) && 1 === count( $corrects ) ) {
			// If value is string and there is just one correct string, we compare two strings.
			$corrects = reset( $corrects );
		} else {
			// If multiple answers, require all of them.
			return $this->check_text_answer_contain_all( $value, $corrects );
		}

		if ( is_numeric( $value ) && is_numeric( $corrects ) ) {
			// If both are numeric, convert to number to compare.
			return floatval( $value ) === floatval( $corrects );
		}

		return $value === $corrects;
	}

	/**
	 * Checks if the user answer contains all correct answers.
	 *
	 * @since 2.0
	 * @param array|string $value    The user answer.
	 * @param array        $corrects The correct answers.
	 *
	 * @return bool
	 */
	protected function check_text_answer_contain_all( $value, $corrects ) {
		$contains = $this->count_answers_in_text( $value, $corrects );
		return $contains === count( $corrects );
	}

	/**
	 * Checks if the user answer contains one of correct answers.
	 *
	 * @since 2.0
	 * @param array|string $value    The user answer.
	 * @param array        $corrects The correct answers.
	 *
	 * @return bool
	 */
	protected function check_text_answer_contain_one( $value, $corrects ) {
		$contains = $this->count_answers_in_text( $value, $corrects );
		return $contains >= 1;
	}

	/**
	 * Checks if the user answer does not contain all correct answers.
	 *
	 * @since 2.0
	 * @param array|string $value    The user answer.
	 * @param array        $corrects The correct answers.
	 *
	 * @return bool
	 */
	protected function check_text_answer_not_contain( $value, $corrects ) {
		$contains = $this->count_answers_in_text( $value, $corrects );
		return empty( $contains );
	}

	/**
	 * Get the total number of correct strings that are included in the answer text.
	 *
	 * @since 2.0
	 * @param array|string $value    The user answer.
	 * @param array        $corrects The correct answers.
	 *
	 * @return int
	 */
	protected function count_answers_in_text( $value, $corrects ) {
		$count = 0;
		foreach ( $corrects as $correct ) {
			if ( false !== strpos( $value, $correct ) ) {
				$count ++;
			}
		}
		return $count;
	}

	/**
	 * Check if the field should be counted in the score.
	 *
	 * @param array $args - includes $args[field], $args[value], $args[saved_answers].
	 *
	 * @since 1.02
	 */
	private function should_count_field( $args ) {
		if ( empty( $args['field'] ) ) {
			return false;
		}

		if ( is_numeric( $args['field'] ) ) {
			$args['field'] = FrmField::getOne( $args['field'] );
		}
		$args['entry'] = $this->entry;

		if ( $this->get_version() === 1 ) {
			$count_field = $this->should_count_field_old( $args );
		} else {
			// Use the new form action settings.
			$count_field = FrmQuizzesAppHelper::field_is_enabled( $args['field']->id, $this->get_form_action() );
		}

		/**
		 * Return true if this field should be scored.
		 *
		 * @since 1.02
		 */
		return apply_filters( 'frm_quiz_score_field', $count_field, $args );
	}

	/**
	 * Get the saved score for this entry.
	 *
	 * @since 2.0
	 *
	 * @return false|float
	 */
	public function get_score() {
		$quiz_field = $this->get_quiz_field();
		if ( empty( $quiz_field ) ) {
			return false;
		}

		$score = FrmEntryMeta::get_entry_meta_by_field( $this->entry_id, $quiz_field->id );
		return false === $score || null === $score ? false : (float) $score;
	}

	/**
	 * Get the grade set in grading scale in global settings for percentage
	 *
	 * @param int $percentage
	 * @return string
	 */
	public function get_grade( $percentage ) {
		if ( '' === $percentage ) {
			return '';
		}

		$quiz_settings = new FrmQuizzesSettings();
		$percentage = round( $percentage, 1 );

		// loop through each set grading scale.
		foreach ( $quiz_settings->settings->grading_scale as $grade_scale ) {
			// check if calculated percentage lies inbetween garding scale start & end percentage.
			if ( $percentage >= $grade_scale['start'] && $percentage <= $grade_scale['end'] ) {
				return $grade_scale['grade'];
			}
		}
		return '';
	}

	/**
	 * Save Quiz score
	 *
	 * @param float $score
	 * @return void
	 */
	private function save_score( $score ) {
		$quiz_field = $this->get_quiz_field();
		if ( empty( $quiz_field ) ) {
			return;
		}

		$this->score_field_id = $quiz_field->id;
		FrmQuizzesFormActionHelper::sync_quiz_score_entry_meta( $this->entry_id, $quiz_field, $score );
	}

	/**
	 * Score using the pre 2.0 method. This can be removed soon.
	 *
	 * @since 2.0
	 *
	 * @return false|int
	 */
	private function calculate_entry_key_score() {
		// _deprecated_function( __FUNCTION__, '2.0' );

		$quiz_id = $this->get_key_id();
		if ( empty( $quiz_id ) || $this->entry_id === $quiz_id ) {
			// Don't score the answer key.
			return false;
		}

		$saved_answers = $this->get_key();
		if ( empty( $saved_answers ) || empty( $this->entry_id ) ) {
			return false;
		}

		$entry = $this->get_entry();

		// loop through each field in entry.
		$score = 0;
		foreach ( $entry->metas as $field_id => $value ) {
			if ( empty( $field_id ) ) {
				continue;
			}

			$field = FrmField::getOne( $field_id );
			if ( empty( $field ) ) {
				continue;
			}

			if ( ! $this->should_count_field( compact( 'field', 'value', 'saved_answers' ) ) ) {
				// Don't grade every field.
				continue;
			}

			// check if field value matches value in saved answers entry.
			$is_correct = isset( $saved_answers->metas[ $field_id ] ) && $this->is_correct( $value, $saved_answers->metas[ $field_id ], $field );
			$is_correct = apply_filters( 'frm_quiz_is_correct', $is_correct, compact( 'field', 'value', 'entry', 'saved_answers' ) );
			if ( $is_correct ) {
				$score++;
			}
		}

		return $score;
	}

	private function is_correct( $answer, $key, $field ) {
		// _deprecated_function( __FUNCTION__, '2.0' );
		$this->flatten_response( $answer );
		$this->flatten_response( $key );

		$open_text = array( 'text', 'textarea' );
		if ( in_array( $field->type, $open_text ) ) {
			return strpos( $answer, $key ) !== false;
		} else {
			return ( $answer == $key );
		}
	}

	/**
	 * Don't require case senstivitity
	 *
	 * @param array|string $response The selected value.
	 */
	private function flatten_response( &$response ) {
		// _deprecated_function( __FUNCTION__, '2.0' );

		if ( is_array( $response ) ) {
			$response = implode( ', ', $response );
		}
		$response = $this->preprocess_check_value( $response );
	}

	private function should_count_field_old( $args ) {
		// _deprecated_function( __FUNCTION__, '2.0', 'should_count_field' );

		$excluded_field_types = FrmQuizzesAppHelper::get_excluded_field_types();

		$count_field = ! in_array( $args['field']->type, $excluded_field_types, true );

		return $count_field;
	}


	/**
	 * Get the number of questions in the key that have an answer.
	 *
	 * @return float
	 */
	private function get_question_count_old() {
		$quiz_key = $this->get_key();
		if ( empty( $quiz_key ) ) {
			return 0;
		}

		foreach ( $quiz_key->metas as $field => $value ) {
			if ( ! $this->should_count_field( compact( 'field', 'value' ) ) ) {
				// don't count the quiz field toward the score.
				unset( $quiz_key->metas[ $field ] );
			}
		}
		return count( $quiz_key->metas );
	}

	public function get_key() {
		// _deprecated_function( __FUNCTION__, '2.0', 'FrmQuizzesMigrationController::get_quiz_key' );
		return FrmQuizzesMigrationController::get_quiz_key( $this->form_id );
	}

	/**
	 * Get quiz key( entry id ) which stores correct values
	 *
	 * @return int
	 */
	public function get_key_id() {
		// _deprecated_function( __FUNCTION__, '2.0', 'FrmQuizzesMigrationController::get_quiz_key_id' );
		return FrmQuizzesMigrationController::get_quiz_key_id( $this->form_id );
	}
}
