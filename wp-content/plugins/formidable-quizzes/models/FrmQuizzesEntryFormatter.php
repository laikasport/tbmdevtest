<?php
/**
 * Class FrmQuizzesEntryFormatter
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

/**
 * Class FrmQuizzesEntryFormatter
 */
class FrmQuizzesEntryFormatter extends FrmProEntryFormatter {

	/**
	 * Quiz action object.
	 *
	 * @var object
	 */
	protected $quiz_action;

	/**
	 * The field value and scoring details.
	 *
	 * @var array
	 * @return void
	 */
	protected $scored_field = array();

	public function __construct( $atts ) {
		parent::__construct( $atts );
		$this->init_quiz_action( $atts );
		$this->enqueue_quiz_style();
	}

	/**
	 * We know the format is a scored quiz, but we want a table.
	 *
	 * @param array $atts The shortcode atts.
	 * @return void
	 */
	protected function init_format( $atts ) {
		if ( $atts['format'] === 'quiz_correct_answers' ) {
			$this->is_plain_text = isset( $atts['plain_text'] ) && $atts['plain_text'];
			$atts['format'] = 'text';
		}

		parent::init_format( $atts );
	}

	/**
	 * Initializes quiz action.
	 *
	 * @param array $atts The atts.
	 * @return void
	 */
	public function init_quiz_action( $atts ) {
		$this->quiz_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $this->entry->form_id, true );
	}

	/**
	 * Set the table_generator property.
	 *
	 * @param array $atts The atts of entry formatter.
	 * @return void
	 */
	protected function init_table_generator( $atts ) {
		$this->table_generator = new FrmQuizzesTableHTMLGenerator( 'entry', $atts );
	}

	/**
	 * @return void
	 */
	private function enqueue_quiz_style() {
		if ( ! FrmQuizzesAppHelper::is_admin_view() ) {
			FrmQuizzesScoredController::enqueue_scripts();
		} else {
			add_action( 'frm_entry_shared_sidebar', 'FrmQuizzesEntriesController::show_score_in_sidebar' );
		}
	}

	/**
	 * Package the value arguments for an HTML row.
	 *
	 * @param FrmProFieldValue $field_value Field value object.
	 * @return array
	 */
	protected function package_value_args( $field_value ) {
		$field = $field_value->get_field();
		if ( ! FrmQuizzesAppHelper::field_is_enabled( $field->id, $this->quiz_action ) ) {
			return parent::package_value_args( $field_value );
		}

		$in_admin = FrmQuizzesAppHelper::is_admin_view();
		$manual_score = ! FrmQuizzesAppHelper::is_choice_field( $field ) && FrmQuizzesAppHelper::field_is_manual( $field->id, $this->quiz_action );

		$scoring      = new FrmQuizzes( array( 'form_action' => $this->quiz_action ) );
		$check_result = $scoring->check_field( $field_value );
		$score        = $check_result['score'] === false ? '-' : (float) $check_result['score'];
		$append       = '';
		if ( in_array( 'score', $this->include_extras, true ) || FrmQuizzesAppHelper::is_admin_view() ) {
			$append = '<span class="frm_total_max">' . esc_html( $score . '/' . $check_result['max'] ) . '</span>';
		}

		if ( $in_admin && $manual_score ) {
			$append .= $this->get_manual_score_field( $field_value, $check_result );
		} elseif ( $manual_score ) {
			// This is shown when it's not the admin page.
			if ( ! $this->is_plain_text ) {
				$append .= '<div class="frm_correct_answer">';
			}
			$append .= FrmQuizzesAppHelper::get_not_scored_message();

			if ( ! $this->is_plain_text ) {
				$append .= '</div>';
			}
		} elseif ( ! $check_result['correct'] ) {
			$append .= $this->add_correct_answers( $field, $check_result );
		}

		$scored_answers = $this->show_scored_user_answers( $field_value, $check_result );
		return array(
			'label'      => $field_value->get_field_label(),
			'value'      => $scored_answers . ( $scored_answers ? $append : '' ),
			'field_type' => $field->type,
			'correct'    => $check_result['correct'],
			'max'        => $check_result['max'],
			'score'      => $check_result['score'],
		);
	}

	/**
	 * Gets manually scoring field label.
	 *
	 * @param FrmProFieldValue $field_value Field value object.
	 * @param array            $result      The scores and max.
	 * @return string
	 */
	protected function get_manual_score_field( $field_value, $result ) {
		if ( $this->is_plain_text ) {
			return '';
		}

		$field_id = $field_value->get_field_id();
		$entry_id = $field_value->get_entry()->id;
		$classes  = 'frm_quizzes_manual_score';

		$score = $result['score'];

		if ( false === $score ) {
			$classes .= ' frm_quizzes_manual_score--not_set';
		}
		wp_enqueue_script( 'wp-backbone' ); // Enqueue this to use wp.ajax.

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div class="frm_quizzes_manual_score__view">
				<span><?php FrmQuizzesManualHelper::show_manual_score_value( $score, true ); ?></span>
				<a href="#" class="frm_quizzes_edit_manual_score">
					<?php esc_html_e( 'Change', 'formidable-quizzes' ); ?>
				</a>
			</div>
			<div class="frm_quizzes_manual_score__edit">
				<input type="text" value="<?php echo false !== $score ? floatval( $score ) : ''; ?>" />
				<button
					type="button"
					class="frm_quizzes_save_manual_score button-primary frm-button-primary"
					data-field-id="<?php echo intval( $field_id ); ?>"
					data-entry-id="<?php echo intval( $entry_id ); ?>"
					style="margin:0 5px;"
				><?php esc_html_e( 'Save', 'formidable-quizzes' ); ?></button>
				<a href="#" class="frm_quizzes_cancel_edit_manual_score">
					<?php FrmAppHelper::icon_by_class( 'frmfont frm_close_icon' ); ?>
				</a>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		// Get rid of the autop.
		$html = str_replace( array( "\r\n", "\n" ), '', $html );
		return $html;
	}

	/**
	 * Answers can be right if all correct answers weren't selected.
	 * This shows which user answers were correct or not in a single field.
	 *
	 * @param FrmProFieldValue $field_value Field value object.
	 * @param array            $result      The scores and max.
	 *
	 * @return string
	 */
	protected function show_scored_user_answers( $field_value, $result ) {
		$how_correct  = $this->how_correct( $result );
		if ( empty( $how_correct ) ) {
			// This isn't scored.
			$field_value->prepare_displayed_value();
			return $field_value->get_displayed_value();
		}

		$field_value->prepare_displayed_value( array( 'return_array' => true ) );
		$array_values = $field_value->get_displayed_value();
		$to_show      = '';

		foreach ( (array) $array_values as $value ) {
			$class = $how_correct === 'correct' ? 'frm_quizzes_correct_bg' : 'frm_quizzes_incorrect_bg';
			if ( in_array( $value, $result['correct_answers'], true ) || in_array( strtolower( $value ), $result['correct_answers'], true ) ) {
				$class = 'frm_quizzes_correct_bg';
			}

			if ( $value !== '' ) {
				$to_show .= '<div class="' . esc_attr( $class ) . '">' . wp_kses_post( $value ) . '</div> ';
			}
		}

		return $to_show;
	}

	/**
	 * Check if the field is scored and whether it's partial correct or not.
	 *
	 * @param array $result Info about the correct answers.
	 *
	 * @return string
	 */
	protected function how_correct( $result ) {
		$scored = isset( $result['correct'] );
		if ( ! $scored || ! $result['max'] ) {
			return '';
		}

		$correct = $result['score'] / $result['max'];

		if ( $correct > 0 && $correct < 1 ) {
			return 'part_correct';
		}

		return $correct >= 1 ? 'correct' : 'incorrect';
	}

	/**
	 * Get the HTML to show the correct answers with the entry.
	 *
	 * @param object $field  The field object.
	 * @param array  $result The scoring details for the field.
	 *
	 * @return string
	 */
	protected function add_correct_answers( $field, $result ) {
		if ( $this->is_plain_text ) {
			return '';
		}

		$correct = $this->format_correct_answers( $result );
		$label   = $result['compare'] === 'not_contain' ? __( 'Should not contain:', 'formidable-quizzes' ) : __( 'Correct answer:', 'formidable-quizzes' );

		$append  = '<div class="frm_correct_answer">';
		$append .= '<span class="frm_correct_answer_label">';
		$append .= esc_html( $label );
		$append .= '</span>';
		$append .= ' <span class="frm_correct_answers">';
		$append .= $this->prepare_display_value_for_html_table( $correct, $field->type );
		$append .= '</span>';
		$append .= '</div>';
		return $append;
	}

	/**
	 * Add the correct class for styling.
	 *
	 * @param array $result
	 * @return string
	 */
	protected function format_correct_answers( $result ) {

		if ( is_array( $result['corrects_raw'] ) ) {
			$and_or  = $result['compare'] === 'contain_one' ? __( 'or', 'formidable-quizzes' ) : __( 'and', 'formidable-quizzes' );
			$correct = '<span>' .
				implode( '</span>, <span>', $result['corrects_raw'] ) .
				'</span>';

			if ( count( $result['corrects_raw'] ) > 1 ) {
				$correct = substr_replace( $correct, "> $and_or <", strrpos( $correct, '>, <' ), 4 );
			}
		} else {
			$correct = $result['corrects_raw'];
		}

		return $correct;
	}

	/**
	 * Get the scoring details and pass them on before adding a row.
	 *
	 * @since 2.01
	 *
	 * @param FrmProFieldValue $field_value
	 * @param string           $content
	 * @return void
	 */
	protected function add_row_for_standard_field( $field_value, &$content ) {
		if ( ! $this->include_field_in_content( $field_value ) ) {
			return;
		}

		$value_args         = $this->package_value_args( $field_value );
		$this->scored_field = $value_args; // Set for later use.
		if ( $this->format === 'plain_text_block' ) {
			$this->add_plain_text_row( $value_args['label'], $value_args['value'], $content );
		} elseif ( $this->format === 'table' ) {
			$this->add_html_row( $value_args, $content );
		}
		$this->scored_field = array();
	}

	/**
	 * Add a row in an HTML table.
	 *
	 * @param array  $value_args The output of {@see FrmQuizzesEntryFormatter::package_value_args()}.
	 * @param string $content    The content.
	 * @return void
	 */
	protected function add_html_row( $value_args, &$content ) {
		$display_value = $this->prepare_display_value_for_html_table( $value_args['value'], $value_args['field_type'] );
		$this->add_correct_mark( $value_args );

		$content .= $this->table_generator->generate_two_cell_table_row( $value_args['label'], $display_value );
	}

	/**
	 * Add a row of values to the plain text content
	 *
	 * @since 2.01
	 *
	 * @param string $label
	 * @param mixed  $display_value
	 * @param string $content
	 * @return void
	 */
	protected function add_plain_text_row( $label, $display_value, &$content ) {
		$value_args = $this->scored_field;
		$this->add_correct_mark( $value_args );

		parent::add_plain_text_row( $value_args['label'], $display_value, $content );
	}

	/**
	 * @param array $value_args
	 * @return void
	 */
	private function add_correct_mark( &$value_args ) {
		$correct = $this->add_correct_or_incorrect( $value_args );
		$label = $value_args['label'];
		if ( ! $this->is_plain_text ) {
			$label = '<span>' . $label . '</span>';
		}
		$value_args['label'] = $correct . ' ' . $label;
	}

	/**
	 * Gets correct cell from correct value.
	 *
	 * @param array $value_args Includes correct value.
	 * @return string
	 */
	private function add_correct_or_incorrect( $value_args ) {
		$class   = '';
		$how_correct = $this->how_correct( $value_args );

		if ( $this->is_plain_text ) {
			$icon = ' ';
			if ( $how_correct === 'correct' ) {
				$icon = '☑';
			} elseif ( $how_correct ) {
				$icon = '☒';
			}
			return $icon;
		}

		if ( $how_correct ) {
			$class = 'frm_quizzes_' . $how_correct;
		}

		$content = '<span class="frm_quizzes_correct_col ' . esc_attr( $class ) . '" style="vertical-align:text-bottom;">';
		if ( $how_correct === 'correct' ) {
			$content .= $this->correct_icon();
		} elseif ( $how_correct ) {
			$content .= $this->incorrect_icon();
		}
		$content .= '</span>';

		return $content;
	}

	/**
	 * Gets correct icon.
	 *
	 * @return string
	 */
	protected function correct_icon() {
		$icon  = '<span class="frm_quizzes_correct_icon">';
		$icon .= '<svg class="frmsvg" id="frm_checkmark_icon" viewBox="0 0 20 20" width="18px" height="18px"><path d="M17 3.3L6.7 13.5 3 9.8a.5.5 0 0 0-.7 0l-1 1c-.3.2-.3.5 0 .7l5.1 5.2c.2.2.5.2.7 0L18.8 5c.2-.2.2-.5 0-.6l-1.1-1.1a.5.5 0 0 0-.7 0z"/></svg>';
		$icon .= '</span>';
		return $icon;
	}

	/**
	 * Gets incorrect icon.
	 *
	 * @return string
	 */
	protected function incorrect_icon() {
		$icon  = '<span class="frm_quizzes_incorrect_icon">';
		$icon .= '<svg class="frmsvg" id="frm_close_icon" viewBox="0 0 20 20" width="18px" height="18px"><path d="M16.8 4.5l-1.3-1.3L10 8.6 4.5 3.2 3.2 4.5 8.6 10l-5.4 5.5 1.3 1.3 5.5-5.4 5.5 5.4 1.3-1.3-5.4-5.5 5.4-5.5z"/></svg>';
		$icon .= '</span>';
		return $icon;
	}

	/**
	 * Which fields to exclude.
	 *
	 * @return array
	 */
	protected function skip_fields() {
		$skip_fields   = parent::skip_fields();
		if ( FrmQuizzesAppHelper::is_admin_view() ) {
			$skip_fields[] = 'quiz_score';
		}
		return $skip_fields;
	}
}
