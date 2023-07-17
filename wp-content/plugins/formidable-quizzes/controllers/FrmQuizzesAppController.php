<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesAppController {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $plug_version = '3.1';

	/**
	 * @var string Minimum required version of Formidable Lite.
	 */
	public static $min_version = '5.4.5';

	/**
	 * @var string Minimum required version of Formidable Pro.
	 */
	private static $min_pro_version = '5.4.5';

	/**
	 * Print out a minimum version notice if Formidable version does not meet minimum requirement.
	 *
	 * @return void
	 */
	public static function min_version_notice() {
		$lite_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : '0';
		$pro_version  = class_exists( 'FrmProDb' ) ? FrmProDb::$plug_version : '0';

		$meets_lite_check = version_compare( $lite_version, self::$min_version, '>=' );
		$meets_pro_check  = version_compare( $pro_version, self::$min_pro_version, '>=' );

		if ( $meets_lite_check && $meets_pro_check ) {
			return;
		}

		// translators: %1$s: Required Add On Name (ie Formidable Forms), %$2s: Minimum version number (ie 5.3).
		$message  = __( 'You are running an outdated version of %1$s. This plugin needs version %2$s+ to work correctly.', 'frmquizzes' );
		$messages = array();

		if ( ! $meets_lite_check ) {
			$messages[] = sprintf( $message, __( 'Formidable Forms', 'frmquizzes' ), self::$min_version );
		}

		if ( ! $meets_pro_check ) {
			$messages[] = sprintf( $message, __( 'Formidable Pro', 'frmquizzes' ), self::$min_pro_version );
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		foreach ( $messages as $message ) {
			?>
			<tr class="plugin-update-tr active">
				<th colspan="<?php echo (int) $wp_list_table->get_column_count(); ?>" class="check-column plugin-update colspanchange">
					<div class="update-message">
						<?php echo esc_html( $message ); ?>
					</div>
				</th>
			</tr>
			<?php
		}
	}

	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include self::path() . '/models/FrmQuizzesUpdate.php';
			FrmQuizzesUpdate::load_hooks();
		}
	}

	public static function path() {
		return dirname( dirname( __FILE__ ) );
	}

	public static function plugin_url() {
		return plugins_url() . '/' . basename( self::path() );
	}

	public static function add_scripts() {
		if ( ! self::is_formidable_compatible() ) {
			return;
		}

		$url = self::plugin_url();

		wp_register_style( 'frm-quizzes-admin', $url . '/css/frm-quizzes-admin.css', array(), self::$plug_version );
		wp_register_script( 'frm-quizzes-admin', $url . '/js/frm-quizzes-admin.js', array( 'wp-util' ), self::$plug_version, true );

		wp_localize_script(
			'frm-quizzes-admin',
			'FrmQuizzesAdminL10n',
			array(
				'ajaxNonce'      => wp_create_nonce( 'frm_quizzes_ajax' ),
				'migrating'      => __( 'Migrating the quizzes database. Please do not leave this page!', 'formidable-quizzes' ),
				'migrateSuccess' => __( 'Database is updated successfully', 'formidable-quizzes' ),
				'migrateFailed'  => __( 'Database updating is not completed!', 'formidable-quizzes' ),
			)
		);

		if ( FrmAppHelper::is_formidable_admin() ) {
			wp_enqueue_style( 'frm-quizzes-admin' );
			wp_enqueue_script( 'frm-quizzes-form-action', $url . '/js/frm-quizzes-form-action.js', array( 'frm-quizzes-admin', 'wp-i18n', 'formidable_dom' ), self::$plug_version, true );
		}

		if ( FrmAppHelper::is_admin_page( 'formidable-settings' ) ) {
			wp_enqueue_script( 'frmquizzes-settings', $url . '/js/frmquizzes-settings.js', array( 'jquery' ), self::$plug_version );
		}
	}

	/**
	 * Check if the current version of Formidable is compatible with this add-on
	 *
	 * @since 1.01
	 * @return bool
	 */
	private static function is_formidable_compatible() {
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : '0';
		return version_compare( $frm_version, '2.0', '>=' );
	}

	/**
	 * Conditional rename the quiz score field for the builder.
	 * This type is shown at the header when the score field is selected.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function add_field( $fields ) {
		$form_id = FrmAppHelper::simple_get( 'id', 'absint' );
		if ( $form_id && FrmQuizzesFormActionHelper::form_has_active_outcomes( $form_id ) ) {
			$name = __( 'Outcome', 'formidable-quizzes' );
		} else {
			$name = __( 'Score', 'formidable-quizzes' );
		}

		$fields['quiz_score'] = array(
			'name' => $name,
			'icon' => '', // This field is hidden so the icon does not matter. It just needs to be set.
		);
		return $fields;
	}

	/**
	 * Include the hidden score field first in the form builder.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function hide_builder_field( $fields ) {
		$fields = self::set_score_order( $fields );
		return $fields;
	}

	public static function add_field_class( $class, $field_type ) {
		if ( 'quiz_score' === $field_type ) {
			$class = 'FrmQuizzesField';
		}
		return $class;
	}

	/**
	 * Calculate Quiz Score on Form Submit & save it in hidden quiz score field
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 *
	 * @return false|float
	 */
	public static function calculate_quiz_score( $entry_id, $form_id = 0 ) {
		$scoring = new FrmQuizzes( compact( 'form_id', 'entry_id' ) );
		return $scoring->calculate_score();
	}

	/**
	 * Changes the fields order.
	 *
	 * @since 2.0
	 *
	 * @param array $fields Fields list.
	 * @param array $args   The arguments.
	 * @return array
	 */
	public static function change_fields_order( $fields, $args ) {
		$random = new FrmQuizzesRandom( $fields, $args );
		$fields = $random->change_fields_order( $fields );
		$fields = self::set_score_order( $fields );
		return $fields;
	}

	/**
	 * Set the quiz score field first so it'll be the first column on the entries table.
	 *
	 * @param array $fields
	 *
	 * @since 2.0
	 */
	public static function set_score_order( $fields ) {
		$score_field = false;
		foreach ( $fields as $k => $field ) {
			if ( $k && FrmField::get_field_type( $field ) === 'quiz_score' ) {
				$score_field = $field;
				unset( $fields[ $k ] );
				array_unshift( $fields, $score_field );
			}
		}
		return $fields;
	}
}
