<?php
/**
 * Class FrmQuizzesEntry
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmQuizzesEntry {

	/**
	 * Entry data.
	 *
	 * @var object
	 */
	public $entry;

	/**
	 * Form ID.
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * Form action data.
	 *
	 * @var object
	 */
	public $form_action;

	public function __construct( $args ) {
		$this->init_entry( $args );
		$this->init_form_id( $args );
		$this->init_form_action();
	}

	protected function init_entry( $args ) {
		if ( empty( $args['entry'] ) ) {
			return;
		}

		if ( is_numeric( $args['entry'] ) ) {
			$this->entry = FrmEntry::getOne( $args['entry'], true );
			return;
		}

		$this->entry = $args['entry'];
	}

	protected function init_form_id( $args ) {
		if ( ! empty( $args['form_id'] ) ) {
			$this->form_id = intval( $args['form_id'] );
		}
	}

	protected function init_form_action() {
		if ( ! $this->form_id ) {
			return;
		}

		$this->form_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $this->form_id );
	}
}
