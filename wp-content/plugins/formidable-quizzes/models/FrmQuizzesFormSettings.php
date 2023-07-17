<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesFormSettings {

	protected static $setting_name = 'frm_quiz_keys';

	/**
	 * Add Form Setting to set Quiz Key
	 *
	 * @param array $values
	 * @return void
	 *
	 * @deprecated 2.0
	 */
	public static function add_setting( $values ) {
		_deprecated_function( __METHOD__, '2.0' );

		$form_id = $values['id'];
		$quiz_key = self::get_key_for_form( $form_id );
		if ( ! $quiz_key ) {
			return;
		}

		include_once FrmQuizzesAppController::path() . '/views/form-settings/settings.php';
	}

	/**
	 * Old: get the first 20 entries in a form.
	 *
	 * @param int $form_id
	 *
	 * @deprecated 2.0
	 */
	public static function get_entries( $form_id ) {
		_deprecated_function( __METHOD__, '2.0' );

		$where = array(
			'form_id' => $form_id,
		);
		return FrmEntry::getAll( $where, '', 20 );
	}

	/**
	 * No longer used.
	 *
	 * @param int $form_id
	 *
	 * @deprecated 2.0
	 */
	public static function add_entry_message( $form_id ) {
		_deprecated_function( __METHOD__, '2.0' );
	}

	/**
	 * Save Quiz Key( Entry id )
	 *
	 * @param array $options
	 *
	 * @return array $options
	 *
	 * @deprecated 2.0
	 */
	public static function save_setting( $options ) {
		_deprecated_function( __METHOD__, '2.0' );
		return $options;
	}

	/**
	 * The old way to get the entry key for scoring.
	 *
	 * @param int $form_id
	 *
	 * @deprecated 2.0
	 */
	public static function get_key_for_form( $form_id ) {
		_deprecated_function( __METHOD__, '2.0' );
		$quiz_keys = self::get_setting();
		$has_key = isset( $quiz_keys[ $form_id ] ) && ! empty( $quiz_keys[ $form_id ] ) && is_numeric( $quiz_keys[ $form_id ] );
		return $has_key ? $quiz_keys[ $form_id ] : '';
	}

	/**
	 * The old way to get the settings for a quiz.
	 *
	 * @deprecated 2.0
	 */
	public static function get_setting() {
		_deprecated_function( __METHOD__, '2.0' );
		$quiz_keys = get_option( self::$setting_name );
		if ( empty( $quiz_keys ) ) {
			$quiz_keys = array();
		}
		return $quiz_keys;
	}
}
