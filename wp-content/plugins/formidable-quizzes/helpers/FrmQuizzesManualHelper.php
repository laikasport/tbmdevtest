<?php
/**
 * Class FrmQuizzesManualHelper
 *
 * Handle manual scoring
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmQuizzesManualHelper {

	/**
	 * Shows manual score value.
	 *
	 * @param float|false $score Score value.
	 * @param bool        $echo  Whether to show or return the string.
	 * @return string|void
	 */
	public static function show_manual_score_value( $score, $echo = false ) {
		if ( false === $score ) {
			$val = FrmQuizzesAppHelper::get_not_scored_message();
		} else {
			// translators: score.
			$val = sprintf( esc_html( _n( '%s point', '%s points', (int) $score, 'formidable-quizzes' ) ), floatval( $score ) );
		}

		if ( ! $echo ) {
			return $val;
		}

		echo esc_html( $val );
	}

	/**
	 * Checks manually scoring field. This method gets the score from the DB and add to the result array.
	 *
	 * @param FrmFieldValue $field_value Field value object.
	 * @param array         $result      The default result response.
	 * @return array
	 */
	public static function check_manually_scoring_field( $field_value, $result ) {
		// Get the score from entry.
		$result['score'] = self::get_field_manual_score( $field_value->get_entry()->id, $field_value->get_field_id() );

		return $result;
	}

	public static function maybe_set_manual_score() {
		$entry_id = FrmAppHelper::get_post_param( 'entry_id', 0, 'intval' );
		$field_id = FrmAppHelper::get_post_param( 'field_id', 0, 'intval' );
		$score    = FrmAppHelper::get_post_param( 'score', -100, 'floatval' );
		if ( ! $entry_id || ! $field_id || $score < -99 ) {
			wp_send_json_error( __( 'Entry ID, Field ID, and Score must not be empty', 'formidable-quizzes' ) );
		}

		$success = self::set_field_manual_score( $entry_id, $field_id, $score );
		if ( ! $success ) {
			wp_send_json_error();
		}

		// Update the total score.
		$scoring  = new FrmQuizzes( compact( 'entry_id' ) );
		$new_total_score = $scoring->calculate_score();

		wp_send_json_success(
			array(
				'score'       => $score,
				'score_text'  => self::show_manual_score_value( $score ),
				'total_score' => $new_total_score,
			)
		);
	}

	/**
	 * Sets manual score for a field.
	 *
	 * @param int   $entry_id Entry ID.
	 * @param int   $field_id Field ID.
	 * @param float $score    Score value.
	 * @return bool|int
	 */
	protected static function set_field_manual_score( $entry_id, $field_id, $score ) {
		$score_key = 'frm_quizzes_field_' . $field_id . '_score';

		return self::set_custom_value_to_entry( $entry_id, $score_key, (float) $score );
	}

	/**
	 * Gets manual score of a field.
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $field_id Field ID.
	 * @return false|float
	 */
	public static function get_field_manual_score( $entry_id, $field_id ) {
		$score_key = 'frm_quizzes_field_' . $field_id . '_score';
		$score     = self::get_custom_value_from_entry( $entry_id, $score_key );
		if ( false === $score ) {
			return false;
		}

		return (float) $score;
	}

	/**
	 * Sets custom value to entry.
	 *
	 * @param int          $entry_id Entry ID.
	 * @param string       $key      Key.
	 * @param float|string $value    Value.
	 */
	protected static function set_custom_value_to_entry( $entry_id, $key, $value ) {
		$meta_value = $key . '_' . $value;
		$current_value = self::get_custom_value_from_entry( $entry_id, $key );

		if ( false === $current_value ) {
			return FrmEntryMeta::add_entry_meta( $entry_id, 0, null, $meta_value );
		}

		global $wpdb;

		return $wpdb->update(
			$wpdb->prefix . 'frm_item_metas',
			array( 'meta_value' => $meta_value ),
			array(
				'meta_value' => $key . '_' . $current_value,
				'item_id'    => $entry_id,
			)
		);
	}


	/**
	 * Gets value of an entry that is not belonged to any fields.
	 *
	 * @param int    $entry_id Entry ID.
	 * @param string $key      Key.
	 * @return string|false    Return `false` if the custom value does not exist.
	 */
	public static function get_custom_value_from_entry( $entry_id, $key ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id = %d AND meta_value LIKE %s",
				intval( $entry_id ),
				$key . '_%'
			)
		);

		if ( ! $result ) {
			return false;
		}

		return str_replace( $key . '_', '', $result );
	}
}
