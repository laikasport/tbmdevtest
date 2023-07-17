<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesField extends FrmFieldHidden {

	/**
	 * The field type.
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $type = 'quiz_score';

	/**
	 * This field collects input.
	 *
	 * @var bool
	 * @since 1.0
	 */
	protected $has_input = false;

	/**
	 * This field type uses the normal HTML.
	 *
	 * @var bool
	 * @since 1.0
	 */
	protected $has_html = false;

	/**
	 * Add a quiz score field if it's missing.
	 *
	 * @param int    $form_id
	 * @param string $name
	 * @return void
	 */
	public static function maybe_add_score_field( $form_id, $name ) {
		$score_field = FrmField::get_all_types_in_form( $form_id, 'quiz_score', 1 );
		if ( $score_field ) {
			self::maybe_update_score_field_name( $score_field, $name );
			return;
		}

		$new_values                = FrmFieldsHelper::setup_new_vars( 'quiz_score', $form_id );
		$new_values['name']        = $name;
		$new_values['field_order'] = 0;
		FrmField::create( $new_values );
	}

	/**
	 * Try to keep the quiz score field name synced. If a scored quiz action is removed and an outcome is added, the field name with update if it is still using the default.
	 *
	 * @param stdClass $score_field
	 * @param string   $name
	 * @return void
	 */
	private static function maybe_update_score_field_name( $score_field, $name ) {
		if ( $name === $score_field->name ) {
			// Nothing to update.
			return;
		}

		if ( ! in_array( $name, array( __( 'Score', 'formidable-quizzes' ), __( 'Outcome', 'formidable-quizzes' ) ), true ) ) {
			// Leave a custom name unmodified.
			return;
		}

		FrmField::update( $score_field->id, array( 'name' => $name ) );
	}

	/**
	 * @return array
	 */
	protected function field_settings_for_type() {
		$settings            = parent::field_settings_for_type();
		$settings['default'] = false;
		return $settings;
	}

	public function prepare_field_html( $args ) {
		$html = '';
		$args = $this->fill_display_field_values( $args );

		if ( FrmAppHelper::is_admin() ) {
			add_action( 'frm_entry_shared_sidebar', 'FrmQuizzesEntriesController::show_score_in_sidebar' );
		}

		$this->field['html_id'] = $args['html_id'];

		ob_start();
		FrmProFieldsHelper::insert_hidden_fields( $this->field, $args['field_name'], $this->field['value'] );
		$html .= ob_get_contents();
		ob_end_clean();

		return $html;
	}

	protected function include_form_builder_file() {
		return FrmQuizzesAppController::path() . '/views/field/backend-builder.php';
	}

	/**
	 * @param mixed $value
	 * @param array $atts {
	 *     @type string|null $show
	 * }
	 * @return string|int
	 */
	protected function prepare_display_value( $value, $atts ) {
		if ( false === $value ) {
			return FrmQuizzesAppHelper::get_not_scored_message();
		}

		if ( ! isset( $atts['show'] ) ) {
			$atts['show'] = '';
		}

		$outcome = $this->maybe_get_outcome_display_value( $value, $atts );
		if ( is_string( $outcome ) ) {
			return $outcome;
		}

		$scoring   = new FrmQuizzes( $this->prepare_scoring_args( $atts ) );
		$max_score = $scoring->get_question_count();

		if ( ! $max_score ) {
			return $value;
		}

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$percentage = ( $value / $max_score ) * 100;

		switch ( $atts['show'] ) {
			case 'percent':
				$value = round( $percentage, 2 ) . '%';
				break;

			case 'grade':
				$value = $scoring->get_grade( $percentage );
				break;

			case 'total':
				// do nothing.
				break;

			default:
				$value = $value . '/' . $max_score;
				break;
		}

		return $value;
	}

	/**
	 * Get the display value for a quiz outcome if one matches $value.
	 *
	 * @param mixed $value
	 * @param array $atts {
	 *     @type string $show
	 *     @type string $entry_id
	 * }
	 * @return string|false
	 */
	private function maybe_get_outcome_display_value( $value, $atts ) {
		if ( ! is_numeric( $value ) ) {
			return false;
		}

		$quiz_outcomes = FrmQuizzesFormActionHelper::get_quiz_outcomes_for_form( $this->field->form_id, true );
		if ( ! $quiz_outcomes ) {
			return false;
		}

		if ( FrmAppHelper::is_admin_page( 'formidable-entries' ) ) {
			// Show the outcome on the View entry page too.
			add_action( 'frm_entry_shared_sidebar', 'FrmQuizzesEntriesController::show_score_in_sidebar' );
		}

		$outcome_id = (int) $value;
		foreach ( $quiz_outcomes as $outcome ) {
			if ( $outcome_id === $outcome->ID ) {
				return $this->get_outcome_string( $outcome, $atts );
			}
		}

		return false;
	}

	/**
	 * Get display string for a given outcome based on $show type.
	 *
	 * @param WP_Post $outcome
	 * @param array   $atts {
	 *     @type string $show Supports 'id', 'title', 'message', 'image', and ''.
	 *     @type string $entry_id
	 * }
	 * @return string
	 */
	private function get_outcome_string( $outcome, $atts ) {
		$show = $atts['show'];
		if ( 'id' === $show ) {
			return (string) $outcome->ID;
		}

		if ( 'title' === $show ) {
			return (string) $outcome->post_title;
		}

		$post_content = $outcome->post_content;
		$message      = (string) $post_content['description'];

		if ( 'message' === $show ) {
			return $message;
		}

		$image = ! empty( $post_content['image'] ) && is_numeric( $post_content['image'] ) ? wp_get_attachment_image( (int) $post_content['image'] ) : '';
		if ( false !== strpos( $image, '<img ' ) ) {
			$image = '<div class="frm-outcome-image-wrapper">' . $image . '</div>';
		}

		if ( 'image' === $show ) {
			return $image;
		}

		if ( 'totals' === $show ) {
			if ( ! $outcome ) {
				return '';
			}
			return $this->show_totals( $atts );
		}

		return $image . $message;
	}

	/**
	 * Show a list of scores for each outcome.
	 *
	 * @since x.x
	 * @param array $atts {
	 *     @type string $show
	 *     @type string $entry_id
	 * }
	 * @return string
	 */
	protected function show_totals( $atts ) {
		// Get the outcome along with all the scores.
		$outcome = FrmQuizzesOutcomeController::get_outcome( $atts['entry_id'] );
		if ( ! $outcome || empty( $outcome->post_content['scores'] ) ) {
			return '';
		}

		$scores = $outcome->post_content['scores'];
		arsort( $scores );

		$result = '<div class="frm_grid_container">';
		foreach ( $scores as $outcome => $score ) {
			$result .= '<div class="frm6 outcome_name">' . esc_html( $outcome ) . '</div>';
			$result .= '<div class="frm6 outcome_score">' . esc_html( $score ) . '</div>';
		}
		$result .= '</div>';

		return $result;
	}

	/**
	 * We need the form id and entry id to pass for scoring.
	 *
	 * @param array $atts Comes from the view or shortcodes.
	 */
	protected function prepare_scoring_args( $atts ) {
		$pass_atts = array(
			'form_id'  => $this->get_field_column( 'form_id' ),
		);

		if ( ! empty( $atts['entry'] ) && ! is_object( $atts['entry'] ) ) {
			// 'entry' can be an object, key, or id.
			$pass_atts['entry']    = FrmEntry::getOne( $atts['entry'] );
		} elseif ( ! empty( $atts['entry'] ) && is_object( $atts['entry'] ) ) {
			$pass_atts['entry'] = $atts['entry'];
		}

		if ( ! empty( $pass_atts['entry'] ) ) {
			$pass_atts['entry_id'] = $pass_atts['entry']->id;
		} elseif ( ! empty( $atts['entry_id'] ) ) {
			$pass_atts['entry_id'] = $atts['entry_id'];
		}

		return $pass_atts;
	}
}
