<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesFormActionHelper {

	/**
	 * Quiz action name.
	 *
	 * @var string
	 */
	public static $action_name = 'quiz';

	/**
	 * Quiz outcome action name.
	 *
	 * @var string
	 */
	public static $outcome_action_name = 'quiz_outcome';

	/**
	 * Gets setting value.
	 *
	 * @param object     $form_action Form action post object.
	 * @param string     $key         Setting key.
	 * @param string|int $nested_key  Nested key.
	 * @param mixed      $default     Default value.
	 * @return mixed
	 */
	public static function get_setting_value( $form_action, $key, $nested_key = '', $default = null ) {
		if ( ! is_object( $form_action ) ) {
			return $default;
		}

		$settings      = $form_action->post_content;
		$quiz_settings = isset( $settings['quiz'] ) ? $settings['quiz'] : array();

		$value = $default;
		if ( isset( $settings[ $key ] ) ) {
			// This is a top-level setting.
			$value = $settings[ $key ];
		} elseif ( isset( $quiz_settings[ $key ] ) ) {
			// Get a setting from the quiz array.
			$value = $quiz_settings[ $key ];
		} elseif ( is_array( $quiz_settings ) && $nested_key && is_numeric( $nested_key ) ) {
			// Find the field id in the quiz array.
			foreach ( $quiz_settings as $sub ) {
				if ( isset( $sub['id'] ) && (int) $sub['id'] === (int) $nested_key ) {
					$value      = $sub;
					$nested_key = $key; // Get the value in the field quiz settings.
					break;
				}
			}
		}

		if ( ! $nested_key ) {
			return $value;
		}

		return ( is_array( $value ) && isset( $value[ $nested_key ] ) ) ? $value[ $nested_key ] : $default;
	}

	/**
	 * Gets correct values for question.
	 *
	 * @param int    $field_id    Field ID.
	 * @param object $quiz_action Quiz action.
	 * @return array
	 */
	public static function get_correct_values( $field_id, $quiz_action ) {
		$corrects = self::get_setting_value( $quiz_action, 'corrects', $field_id, array() );
		if ( ! is_array( $corrects ) ) {
			return array();
		}

		return array_filter(
			$corrects,
			function( $answer ) {
				return ! self::is_empty_answer( $answer );
			}
		);
	}

	/**
	 * Checks if answer is empty. Zero value will return false.
	 *
	 * @param mixed $answer The answer.
	 *
	 * @return bool
	 */
	public static function is_empty_answer( $answer ) {
		return null === $answer || false === $answer || '' === trim( $answer );
	}

	/**
	 * Gets all possible answers from a choice field.
	 *
	 * @param object $field Field object.
	 * @return array
	 */
	public static function get_all_choice_answers_from_field( $field ) {
		switch ( $field->type ) {
			case 'radio':
			case 'checkbox':
			case 'select':
				$answers = array();
				foreach ( $field->options as $option ) {
					if ( isset( $option['value'] ) ) {
						if ( empty( $option['label'] ) && $option['value'] !== '' ) {
							// If label is empty but value is not, use value as label.
							$option['label'] = $option['value'];
						}
						$answers[] = $option;
					} else {
						$answers[] = array(
							'label' => $option,
							'value' => $option,
						);
					}
				}
				break;

			default:
				$answers = array();
		}

		/**
		 * Allows modifying answers of a field.
		 *
		 * @since 2.0.0
		 *
		 * @param array $answers The list of answers.
		 * @param array $args    {
		 *     The arguments.
		 *
		 *     @type object $field Field data.
		 * }
		 */
		return apply_filters( 'frm_quizzes_get_all_answers', $answers, compact( 'field' ) );
	}

	/**
	 * Shows the modal header.
	 *
	 * @param string $modal_title Modal title.
	 */
	public static function modal_header( $modal_title ) {
		include FrmQuizzesAppController::path() . '/views/shared/modal-header.php';
	}

	/**
	 * Shows the modal footer.
	 */
	public static function modal_footer() {
		include FrmQuizzesAppController::path() . '/views/shared/modal-footer.php';
	}

	/**
	 * Gets quiz action from form.
	 *
	 * @param int  $form_id Form ID.
	 * @param bool $active  Return active action only.
	 *
	 * @return WP_Post|null
	 */
	public static function get_quiz_action_from_form( $form_id, $active = false ) {
		$args    = array(
			'post_status' => $active ? 'publish' : 'all',
		);
		$actions = FrmFormAction::get_action_for_form( $form_id, self::$action_name, $args );
		if ( ! $actions ) {
			return null;
		}

		return reset( $actions );
	}

	/**
	 * Check if a form has outcomes.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id
	 * @return bool
	 */
	public static function form_has_active_outcomes( $form_id ) {
		$args = array(
			'post_status' => 'publish',
			'limit'       => 1,
		);
		return (bool) FrmFormAction::get_action_for_form( $form_id, self::$outcome_action_name, $args );
	}

	/**
	 * Get all outcomes for a form.
	 *
	 * @since 3.0
	 *
	 * @param int  $form_id
	 * @param bool $active Return active action only.
	 *
	 * @return array<WP_Post>
	 */
	public static function get_quiz_outcomes_for_form( $form_id, $active = false ) {
		$args = array(
			'post_status' => $active ? 'publish' : 'all',
		);
		return FrmFormAction::get_action_for_form( $form_id, self::$outcome_action_name, $args );
	}

	/**
	 * Gets quiz action from entry ID.
	 *
	 * @param int  $entry_id Entry ID.
	 * @param bool $active   Return active action only.
	 *
	 * @return object|null
	 */
	public static function get_quiz_action_from_entry_id( $entry_id, $active = false ) {
		$form_id = FrmDb::get_var( 'frm_items', array( 'id' => $entry_id ), 'form_id' );
		if ( ! $form_id ) {
			return null;
		}

		return self::get_quiz_action_from_form( $form_id, $active );
	}

	/**
	 * Gets CSS class for choice answer in settings.
	 *
	 * @param object $field Field data.
	 * @return string
	 */
	public static function get_class_for_choice_answer_setting( $field ) {
		$classes = '';
		if ( ! empty( $field->field_options['image_options'] ) || ! empty( $field->field_options['use_images_in_buttons'] ) ) {
			$classes = 'frm_quizzes_has_image';
		}

		return $classes;
	}

	/**
	 * Maybe show the image for choice answer in settings.
	 *
	 * @param array  $answer Answer array.
	 * @param string $is_image If images are turned on, this will be a class name.
	 */
	public static function maybe_show_choice_answer_image( $answer, $is_image ) {
		if ( $is_image && ! empty( $answer['image'] ) && is_numeric( $answer['image'] ) ) {
			echo wp_get_attachment_image( (int) $answer['image'] );
		}
	}

	/**
	 * Sync outcome or quiz score with item meta data so it interacts properly with other actions.
	 *
	 * @since 3.0
	 *
	 * @param int      $entry_id
	 * @param stdClass $quiz_field
	 * @param mixed    $value Either an outcome post ID or a scored quiz score value.
	 * @return void
	 */
	public static function sync_quiz_score_entry_meta( $entry_id, $quiz_field, $value ) {
		$updated = FrmEntryMeta::update_entry_meta( $entry_id, $quiz_field->id, null, $value );
		if ( ! $updated ) {
			// Add the row if it wasn't there to update.
			FrmEntryMeta::add_entry_meta( $entry_id, $quiz_field->id, null, $value );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['item_meta'] ) ) {
			FrmEntriesHelper::set_posted_value( $quiz_field, $value, array( 'id' => $quiz_field->id ) );
		}

		FrmEntry::clear_cache();
	}
}
