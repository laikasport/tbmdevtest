<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Extend the form actions with quiz settings.
 *
 * @since 2.0
 */
class FrmQuizzesAction extends FrmFormAction {

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_percent_icon frm_quiz_icon frm_icon_font',
			'limit'    => 1,
			'active'   => true,
			'priority' => 9,
			'event'    => array( 'create', 'update', 'import' ),
		);

		$this->FrmFormAction( FrmQuizzesFormActionHelper::$action_name, __( 'Scored Quiz', 'formidable-quizzes' ), $action_ops );
	}

	/**
	 * Make sure the form includes a Quiz score.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @since 2.0
	 */
	public function update( $new_instance, $old_instance ) {
		$form_id = $new_instance['menu_order'];
		$this->maybe_add_score_field( $form_id );
		$this->prepare_settings_before_update( $new_instance );

		return $new_instance;
	}

	/**
	 * Add a quiz score field if it's missing.
	 *
	 * @param int $form_id
	 * @return void
	 */
	protected function maybe_add_score_field( $form_id ) {
		FrmQuizzesField::maybe_add_score_field( $form_id, __( 'Score', 'formidable-quizzes' ) );
	}

	/**
	 * Remove field ids from keys so they don't need switching on import.
	 * Clean up settings before they are saved.
	 *
	 * @param array $new_instance
	 * @return void
	 */
	protected function prepare_settings_before_update( &$new_instance ) {
		if ( empty( $new_instance['post_content']['quiz'] ) || ! is_array( $new_instance['post_content']['quiz'] ) ) {
			return;
		}

		foreach ( $new_instance['post_content']['quiz'] as $k => $values ) {
			if ( ! is_numeric( $k ) ) {
				continue;
			}

			if ( isset( $values['scores'] ) && empty( $values['adv_scoring'] ) ) {
				// Cut down on saved settings size when advanced scoring isn't used.
				unset( $values['scores'] );
			}
			$new_instance['post_content']['quiz'][] = $values;
			unset( $new_instance['post_content']['quiz'][ $k ] );
		}
	}

	/**
	 * Shows form action content.
	 *
	 * @param WP_Post $form_action Form action post object.
	 * @param array   $args        Arguments. Includes `form`, `action_key` and `values`.
	 * @return void
	 */
	public function form( $form_action, $args = array() ) {
		if ( empty( $args['form'] ) ) {
			return;
		}

		$this->print_settings( $form_action );
		$this->print_modal( $form_action );
	}

	/**
	 * @param WP_Post $form_action
	 * @return void
	 */
	private function print_settings( $form_action ) {
		$settings = $this->get_common_settings();
		include FrmQuizzesAppController::path() . '/views/form-actions/settings.php';
	}

	private function print_modal( $form_action ) {
		$fields = $this->get_fields_for_settings( $form_action );
		include FrmQuizzesAppController::path() . '/views/form-actions/scored/modal.php';
	}

	/**
	 * Gets fields for scored quiz settings.
	 *
	 * @param object $form_action Form action post object.
	 * @param string $quiz_type
	 * @return array
	 */
	protected function get_fields_for_settings( $form_action, $quiz_type = 'scored' ) {
		$form_id    = $this->get_form_id_from_action( $form_action );
		$query_args = array(
			'fi.form_id'  => $form_id,
			'fi.type not' => FrmQuizzesAppHelper::get_excluded_field_types(),
		);
		$fields     = FrmField::getAll( $query_args, 'field_order' );

		/**
		 * Allows modifying fields for settings of a quiz type.
		 *
		 * @since 2.0
		 *
		 * @param array $fields List of fields.
		 * @param array $args   Includes `form_action`, `quiz_type`.
		 */
		return apply_filters( 'frm_quizzes_fields_for_settings', $fields, compact( 'form_action', 'quiz_type' ) );
	}

	/**
	 * Gets form ID from form action.
	 *
	 * @param object $form_action Form action post object.
	 * @return int
	 */
	private function get_form_id_from_action( $form_action ) {
		return isset( $form_action->menu_order ) ? (int) $form_action->menu_order : 0;
	}

	/**
	 * Gets default setting values.
	 *
	 * @return array
	 */
	public function get_defaults() {
		$defaults              = parent::get_defaults();
		$defaults['event']     = array( 'create', 'update' );
		$defaults['quiz_type'] = 'scored';

		$settings = $this->get_common_settings();
		foreach ( $settings as $key => $setting ) {
			if ( isset( $setting['default'] ) ) {
				$defaults[ $key ] = $setting['default'];
			}
			unset( $setting );
		}

		$defaults['enable'] = array(); // Array of field ids.
		$defaults['quiz']   = array(); // Array of quiz settings.

		return $defaults;
	}

	/**
	 * Gets quiz action common settings.
	 *
	 * @return array
	 */
	protected function get_common_settings() {
		$settings = array(
			'show_result'       => array(
				'label'   => __( 'What would you like to show after submit?', 'formidable-quizzes' ),
				'type'    => 'select',
				'options' => $this->get_show_result_options(),
				'default' => '',
				'class'   => 'frm6',
			),
			'random_questions'  => array(
				'label'   => __( 'Randomize questions', 'formidable-quizzes' ),
				'type'    => 'toggle',
				'help'    => __( 'Show the fields in a random order each time the form is loaded', 'formidable-quizzes' ),
				'default' => '',
			),
			'random_options'    => array(
				'label'   => __( 'Randomize options', 'formidable-quizzes' ),
				'type'    => 'toggle',
				'help'    => __( 'Change the order of options in a field with multiple options (radio, checkbox, select)', 'formidable-quizzes' ),
				'default' => '',
			),
			'negative_score' => array(
				'label'   => __( 'Allow negative scoring', 'formidable-quizzes' ),
				'type'    => 'toggle',
				'help'    => __( 'Applicable only for questions with advanced scoring', 'formidable-quizzes' ),
				'default' => '',
			),
		);

		/**
		 * Allows modifying quiz action common settings.
		 *
		 * @since 2.0.0
		 *
		 * @param array $settings Settings.
		 */
		return apply_filters( 'frm_quizzes_action_common_settings', $settings );
	}

	/**
	 * Gets show result options.
	 *
	 * @return array
	 */
	protected function get_show_result_options() {
		return array(
			''                => __( 'Use default success settings', 'formidable-quizzes' ),
			'score'           => __( 'Show the score', 'formidable-quizzes' ),
			'user_answers'    => __( 'Show user\'s answers', 'formidable-quizzes' ),
			'correct_answers' => __( 'Show correct answers', 'formidable-quizzes' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_switch_fields() {
		return array(
			'enable' => array(),
			'quiz'   => array( array( 'id' ) ),
		);
	}
}
