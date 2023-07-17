<?php
/**
 * Class FrmQuizzesEntriesController
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmQuizzesEntriesController {

	/**
	 * Array used with entries_column_value filter.
	 * Tracks outcome information by form id.
	 *
	 * @var array
	 */
	private static $expected_column_info = array();

	/**
	 * Changes entry formatter class name.
	 *
	 * @param string $formatter_class Entry formatter class name.
	 * @param array  $atts            See {@see FrmEntriesController::show_entry_shortcode()}.
	 * @return string
	 */
	public static function change_entry_formatter_class( $formatter_class, $atts ) {
		$in_admin     = FrmQuizzesAppHelper::is_admin_view();
		$show_answers = $atts['format'] === 'quiz_correct_answers';
		if ( ! $in_admin && ! $show_answers ) {
			return $formatter_class;
		}

		$form_id = self::get_form_id_from_entry_shortcode_atts( $atts );
		if ( ! $form_id ) {
			return $formatter_class;
		}

		$quiz_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $form_id, true );
		if ( $quiz_action ) {
			return 'FrmQuizzesEntryFormatter';
		}

		return $formatter_class;
	}

	/**
	 * Gets form ID from entry shortcode atts.
	 *
	 * @param array $atts Entry shortcode atts.
	 * @return int|false
	 */
	protected static function get_form_id_from_entry_shortcode_atts( $atts ) {
		if ( ! empty( $atts['form_id'] ) ) {
			return $atts['form_id'];
		}

		if ( ! empty( $atts['entry']->form_id ) ) {
			return $atts['entry']->form_id;
		}

		if ( ! empty( $atts['id'] ) ) {
			$entry = FrmEntry::getOne( $atts['id'] );
			return $entry->form_id;
		}

		return false;
	}

	/**
	 * Shows total score in entry sidebar.
	 *
	 * @param stdClass $entry Entry object.
	 * @return void
	 */
	public static function show_score_in_sidebar( $entry ) {
		$scoring = new FrmQuizzes( compact( 'entry' ) );
		$score   = $scoring->get_score();
		unset( $scoring );

		$icon_class = '';

		if ( false !== $score ) {
			if ( FrmQuizzesFormActionHelper::form_has_active_outcomes( $entry->form_id ) ) {
				$quiz_field   = FrmField::get_all_types_in_form( $entry->form_id, 'quiz_score', 1 );
				$field_object = FrmFieldFactory::get_field_object( $quiz_field );

				/* translators: %s: Outcome result (Name of action/outcome). */
				$sidebar_title = __( 'Outcome: %s', 'formidable-quizzes' );
				$icon_class    = 'frm_check1_icon';
				$score         = $field_object->get_display_value( $score, array( 'show' => 'title' ) );
			}
		}

		if ( empty( $sidebar_title ) ) {
			// Did not match for outcomes, so treat as scored.

			if ( false === $score ) {
				$score = FrmQuizzesAppHelper::get_not_scored_message();
			}

			/* translators: %s: Total score number. */
			$sidebar_title = __( 'Total Score: %s', 'formidable-quizzes' );
			$icon_class    = 'frm_percent_icon';
		}

		$link = FrmQuizzesAppHelper::is_admin_view() ? '' : admin_url( 'admin.php?page=formidable-entries&frm_action=show&id=' . absint( $entry->id ) );
		?>
		<div class="misc-pub-section">
			<?php FrmAppHelper::icon_by_class( $icon_class . ' frm_quiz_icon frm_icon_font', array( 'aria-hidden' => 'true' ) ); ?>
			<span>
				<?php
				printf(
					/* translators: the score */
					esc_html( $sidebar_title ),
					'<strong id="frm_quizzes_total_score">' .
					( $link ? '<a href="' . esc_url( $link ) . '">' : '' ) .
					esc_html( (string) $score ) .
					( $link ? '</a>' : '' ) .
					'</strong>'
				);
				?>
			</span>
		</div>
		<?php
	}

	/**
	 * Show the outcome title for quiz outcome field in form entries list.
	 *
	 * @since 3.0
	 *
	 * @param mixed $val
	 * @param array $args {
	 *     @type stdClass $item     Formidable entry object.
	 *     @type string   $col_name Name of the column. Quiz score field uses its field key.
	 * }
	 * @return mixed
	 */
	public static function entries_column_value( $val, $args ) {
		$expected_column_name = self::get_expected_column_name( $args['item']->form_id );
		if ( false === $expected_column_name || $expected_column_name !== $args['col_name'] ) {
			return $val;
		}

		$entry          = $args['item'];
		$score_field_id = self::$expected_column_info[ $entry->form_id ]['id'];

		if ( ! isset( $args['item']->metas[ $score_field_id ] ) ) {
			return $val;
		}

		$outcome_action_id = $args['item']->metas[ $score_field_id ];
		$field_object      = FrmFieldFactory::get_field_object( $score_field_id );

		return $field_object->get_display_value( $outcome_action_id, array( 'show' => 'title' ) );
	}

	/**
	 * Check form for expected column name for quiz outcome field.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id
	 * @return string|false False if the form does not have a quiz outcome field.
	 */
	private static function get_expected_column_name( $form_id ) {
		self::maybe_set_expected_column_info( $form_id );
		return is_array( self::$expected_column_info[ $form_id ] ) ? self::$expected_column_info[ $form_id ]['field_key'] : false;
	}

	/**
	 * Set come field information for form to an array of column info if it isn't already set.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id
	 * @return void
	 */
	private static function maybe_set_expected_column_info( $form_id ) {
		if ( isset( self::$expected_column_info[ $form_id ] ) ) {
			return;
		}

		$quiz_field = FrmField::get_all_types_in_form( $form_id, 'quiz_score', 1 );
		if ( ! $quiz_field ) {
			self::$expected_column_info[ $form_id ] = false;
			return;
		}

		if ( ! FrmQuizzesFormActionHelper::form_has_active_outcomes( $form_id ) ) {
			self::$expected_column_info[ $form_id ] = false;
			return;
		}

		self::$expected_column_info[ $form_id ] = array(
			'field_key' => $quiz_field->field_key,
			'id'        => $quiz_field->id,
		);
	}

	/**
	 * Show the outcome title for graphs instead of the outcome ID.
	 *
	 * @since 3.0
	 *
	 * @param string|int      $displayed_value
	 * @param stdClass|string $field
	 * @return string|int
	 */
	public static function graph_value( $displayed_value, $field ) {
		if ( ! is_object( $field ) || ! is_numeric( $displayed_value ) || ! isset( $field->type ) || 'quiz_score' !== $field->type ) {
			return $displayed_value;
		}

		if ( ! FrmQuizzesFormActionHelper::form_has_active_outcomes( $field->form_id ) ) {
			return $displayed_value;
		}

		$field_object = FrmFieldFactory::get_field_object( $field );
		return $field_object->get_display_value( $displayed_value, array( 'show' => 'title' ) );
	}
}
