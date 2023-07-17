<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Extend the form actions with quiz settings.
 *
 * @since 3.0
 */
class FrmQuizzesOutcomeAction extends FrmFormAction {

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_check1_icon frm_quiz_icon frm_icon_font',
			'limit'    => 99,
			'active'   => true,
			'priority' => 10,
			'event'    => array( 'create', 'update', 'import' ),
		);

		$this->FrmFormAction( FrmQuizzesFormActionHelper::$outcome_action_name, __( 'Quiz Outcome', 'formidable-quizzes' ), $action_ops );
	}

	/**
	 * Make sure the form includes a Quiz score.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 */
	public function update( $new_instance, $old_instance ) {
		$form_id = $new_instance['menu_order'];
		FrmQuizzesField::maybe_add_score_field( $form_id, __( 'Outcome', 'formidable-quizzes' ) );
		return $new_instance;
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

		$form     = $args['form'];
		$settings = $this->get_type_settings();

		include FrmQuizzesAppController::path() . '/views/form-actions/settings.php';
	}

	/**
	 * Gets default setting values.
	 *
	 * @return array
	 */
	public function get_defaults() {
		$defaults              = parent::get_defaults();
		$defaults['event']     = array( 'create', 'update' );
		$defaults['quiz_type'] = 'outcome';

		$settings = $this->get_type_settings();
		foreach ( $settings as $key => $setting ) {
			if ( isset( $setting['default'] ) ) {
				$defaults[ $key ] = $setting['default'];
			}
			unset( $setting );
		}

		return $defaults;
	}

	/**
	 * Gets quiz type settings.
	 *
	 * @return array
	 */
	private function get_type_settings() {
		$settings = array(
			'image'       => array(
				'label'   => __( 'Image', 'formidable-quizzes' ),
				'type'    => 'image',
				'default' => '',
				'class'   => 'frm4',
			),
			'description' => array(
				'label'   => __( 'Message', 'formidable-quizzes' ),
				'type'    => 'rte',
				'default' => '',
				'class'   => 'frm12',
			),
		);

		/**
		 * Allows modifying outcome quiz action settings.
		 *
		 * @since 3.0
		 *
		 * @param array $settings Settings.
		 */
		return apply_filters( 'frm_quizzes_outcome_action_settings', $settings );
	}

}
