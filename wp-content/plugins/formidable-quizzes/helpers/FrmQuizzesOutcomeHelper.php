<?php
/**
 * Class FrmQuizzesOutcomeHelper
 *
 * Handle outcome weighting.
 *
 * @package FrmQuizzes
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * The Outcome Helper takes an array of outcomes and an entry and it will check the conditions and weigh the best match.
 */
class FrmQuizzesOutcomeHelper {

	/**
	 * @var array<WP_Post> $outcomes
	 */
	private $outcomes;

	/**
	 * @var stdClass $entry
	 */
	private $entry;

	/**
	 * @param array<WP_Post> $outcomes
	 * @param stdClass       $entry
	 * @return void
	 */
	public function __construct( $outcomes, $entry ) {
		$this->outcomes = $outcomes;
		$this->entry    = $entry;
	}

	/**
	 * Check against $this->outcomes for the best match for $this->entry.
	 *
	 * @return WP_Post|false
	 */
	public function get_outcome() {
		if ( ! is_callable( 'FrmProFormActionsController::prepare_logic_value' ) || ! is_callable( 'FrmProFormActionsController::get_value_from_entry' ) ) {
			return false;
		}

		$max      = false;
		$fallback = false;
		$scores   = array();

		foreach ( $this->outcomes as $outcome ) {
			$post_content = (array) $outcome->post_content;

			if ( $this->outcome_has_no_content( $post_content ) ) {
				// Avoid an outcome that would apepar as blank so we don't redirect users to an empty page.
				continue;
			}

			$conditions = $this->filter_conditions( $post_content );

			if ( ! $conditions ) {
				if ( false === $fallback ) {
					// Use the first outcome with no conditions as a fallback outcome.
					$fallback = $outcome;
				}
				continue;
			}

			$score = 0;
			foreach ( $conditions as $condition ) {
				if ( $this->entry_meets_condition( $condition ) ) {
					++$score;
				}
				unset( $condition );
			}

			$scores[ $outcome->post_title ] = $score;
			if ( 0 === $score ) {
				continue;
			}

			if ( false === $max || $score > $max['score'] ) {
				$max = array(
					'outcome' => $outcome,
					'score'   => $score,
				);
			}
		}

		$outcome = is_array( $max ) ? $max['outcome'] : $fallback;
		if ( $outcome && is_array( $outcome->post_content ) ) {
			// Pass the total scores for each outcome.
			/**
			 * @psalm-suppress InvalidPropertyAssignmentValue
			 */
			$outcome->post_content['scores'] = $scores;
		}

		return $outcome;
	}

	/**
	 * Check if an outcome has no wysiwyg content and no image uploaded.
	 *
	 * @param array $post_content
	 * @return bool True if neither of the expected keys are non-empty.
	 */
	private function outcome_has_no_content( $post_content ) {
		return empty( $post_content['description'] ) && empty( $post_content['image'] );
	}

	/**
	 * Check if entry value meets a single condition.
	 *
	 * @param array $condition {
	 *     @type string $hide_opt
	 *     @type string $hide_field
	 *     @type string $hide_field_cond
	 * }
	 * @return bool
	 */
	private function entry_meets_condition( $condition ) {
		FrmProFormActionsController::prepare_logic_value( $condition['hide_opt'], $this->entry );
		$observed_value = FrmProFormActionsController::get_value_from_entry( $this->entry, $condition['hide_field'] );
		return FrmFieldsHelper::value_meets_condition( $observed_value, $condition['hide_field_cond'], $condition['hide_opt'] );
	}

	/**
	 * Remove the condition keys like 'any_all' and 'send_stop' so we can just work with actual conditions.
	 *
	 * @param array $post_content
	 * @return array
	 */
	private function filter_conditions( $post_content ) {
		if ( empty( $post_content['conditions'] ) ) {
			return array();
		}

		return array_filter(
			$post_content['conditions'],
			function( $value, $key ) {
				return is_numeric( $key );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
}
