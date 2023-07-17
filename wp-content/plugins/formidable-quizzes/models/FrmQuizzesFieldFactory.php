<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

/**
 * @since 3.0
 */
class FrmQuizzesFieldFactory {

	/**
	 * Maybe create an instance of an FrmQuizzesFieldValueSelector object
	 *
	 * @since 3.0
	 *
	 * @param FrmFieldValueSelector|null $selector
	 * @param int                        $field_id
	 * @param array                      $selector_args
	 *
	 * @return FrmFieldValueSelector|null
	 */
	public static function create_field_value_selector( $selector, $field_id, $selector_args ) {
		$type = FrmField::get_type( $field_id );
		if ( 'quiz_score' !== $type ) {
			return $selector;
		}

		$form_id = FrmDb::get_var( 'frm_fields', array( 'id' => $field_id ), 'form_id' );
		if ( ! FrmQuizzesFormActionHelper::form_has_active_outcomes( $form_id ) ) {
			return $selector;
		}

		return new FrmQuizzesFieldValueSelector( $field_id, $selector_args );
	}

}
