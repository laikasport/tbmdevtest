<?php
/**
 * Class FrmQuizzesMigrationController
 *
 * Handle database migration for v2.0
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmQuizzesMigrationController {

	protected static $new_db_version = 3;

	protected static $option_name = 'frm_quizzes_db_version';

	public static function migrated_to_v2() {
		return get_option( self::$option_name ) >= 3;
	}

	protected static function update_db_version() {
		update_option( self::$option_name, self::$new_db_version );
	}

	public static function show_notice() {
		if ( ! current_user_can( 'frm_edit_forms' ) ) {
			// Only show the update message to those with permission.
			return;
		}

		if ( ! self::old_quiz_keys() ) {
			self::update_db_version();
			return;
		}

		wp_enqueue_script( 'frm-quizzes-admin' );

		?>
		<div class="notice notice-error error frm_previous_install" style="display:block">
			<p>
				<?php esc_html_e( 'Your quizzes database is out of date.', 'formidable-quizzes' ); ?>
				<button type="button" class="button button-secondary frm-button-secondary" id="frm-quizzes-migrate"><?php esc_html_e( 'Upgrade database', 'formidable-quizzes' ); ?></button>
			</p>
		</div>
		<?php
	}

	private static function old_quiz_keys() {
		return get_option( 'frm_quiz_keys' );
	}

	public static function ajax_migrate() {
		check_ajax_referer( 'frm_quizzes_ajax' );
		FrmAppHelper::permission_check( 'frm_edit_forms' );

		wp_send_json( array( 'success' => self::migrate_forms() ) );
	}

	private static function migrate_forms() {
		$quiz_keys = self::old_quiz_keys();
		foreach ( $quiz_keys as $form_id => $entry_id ) {
			$entry = FrmEntry::getOne( $entry_id, true );
			if ( ! $entry ) {
				unset( $quiz_keys[ $form_id ] );
				continue;
			}

			$form = FrmForm::getOne( $form_id );
			if ( ! $form ) {
				unset( $quiz_keys[ $form_id ] );
				continue;
			}

			if ( ! FrmField::get_all_types_in_form( $form_id, 'quiz_score', 1 ) ) {
				unset( $quiz_keys[ $form_id ] );
				continue;
			}

			$quiz_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $form_id );
			if ( $quiz_action ) { // Do not update existing quiz action.
				unset( $quiz_keys[ $form_id ] );
				continue;
			}

			$action_id = self::migrate( $form, $entry );
			if ( $action_id && ! is_wp_error( $action_id ) ) {
				unset( $quiz_keys[ $form_id ] );
			}
		}

		if ( empty( $quiz_keys ) ) {
			delete_option( 'frm_quiz_keys' );
			self::update_db_version();
			return true;
		}

		update_option( 'frm_quiz_keys', $quiz_keys );
		return false;
	}

	private static function migrate( $form, $entry ) {
		$post_content = array(
			'enable' => array(),
			'quiz'   => array(),
		);

		foreach ( $entry->metas as $field_id => $value ) {
			if ( in_array( FrmField::get_type( $field_id ), FrmQuizzesAppHelper::get_excluded_field_types(), true ) ) {
				continue;
			}

			$corrects  = $value;
			$field_obj = FrmFieldFactory::get_field_object( $field_id );
			if ( $field_obj->is_combo_field ) {
				$corrects = (array) $field_obj->get_display_value( $corrects );
			}

			$post_content['enable'][] = $field_id;
			$post_content['quiz'][]   = array(
				'id'       => $field_id,
				'score'    => 1,
				'corrects' => (array) $corrects,
			);
		}

		return self::create_quiz_action( $form->id, $post_content );
	}

	/**
	 * Creates quiz action.
	 *
	 * @param int   $form_id      Form ID.
	 * @param array $post_content Action settings.
	 * @return int|WP_Error
	 */
	protected static function create_quiz_action( $form_id, $post_content ) {
		$action_class = new FrmQuizzesAction();
		$form_action  = $action_class->prepare_new( $form_id );

		$form_action->post_content = wp_parse_args( $post_content, $form_action->post_content );

		return $action_class->save_settings( $form_action );
	}

	public static function get_quiz_key( $form_id ) {
		$quiz_key = self::get_quiz_key_id( $form_id );
		if ( empty( $quiz_key ) ) {
			return false;
		}

		$saved_answers = FrmEntry::getOne( $quiz_key, true );
		if ( empty( $saved_answers ) ) {
			return false;
		}

		return $saved_answers;
	}

	/**
	 * Get the id of the old entry key for a form.
	 *
	 * @param int $form_id
	 *
	 * @return int
	 */
	public static function get_quiz_key_id( $form_id ) {
		$quiz_keys = self::old_quiz_keys();

		// Check if form has quiz key saved.
		if ( ! is_array( $quiz_keys ) || ! isset( $quiz_keys[ $form_id ] ) ) {
			return 0;
		}

		return (int) $quiz_keys[ $form_id ];
	}
}
