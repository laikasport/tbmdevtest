<?php
/**
 * Show score after submitting form
 *
 * @package FrmQuizzes
 * @since 2.0
 *
 * @var object $quiz_action  Quiz action object.
 * @var float  $total_score  The total score.
 * @var string $message      The submission message.
 * @var array  $answers_count Answers count data.
 * @var float  $percent      The percent score.
 * @var string $class        The class to include on the container.
 * @var string $bg_img       The url of the bg image.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}
?>
<div class="frm_quizzes_show_score <?php echo esc_attr( $class ); ?>">
	<?php if ( ! empty( $bg_img ) ) { ?>
	<img src="<?php echo esc_url( $bg_img ); ?>" alt="" class="frm_quizzes_show_score__bg">
	<?php } ?>
	<div class="frm_quizzes_show_score__top_text">
		<?php esc_html_e( 'Your total score is', 'formidable-quizzes' ); ?>
	</div>

	<div class="frm_quizzes_show_score__score">
		<?php echo floatval( $total_score ); ?>
	</div>

	<div class="frm_quizzes_show_score__bottom_text">
		<?php
		printf(
			// translators: %1$s: correct answers count text, %2$d: total answers count.
			esc_html__( '%1$s out of %2$d', 'formidable-quizzes' ),
			sprintf(
				esc_html(
					// translators: number of correct answers.
					_n( '%d point', '%d points', $total_score, 'formidable-quizzes' )
				),
				intval( $total_score )
			),
			intval( $answers_count )
		);
		?>
	</div>

	<div class="frm_quizzes_show_score__message">
		<?php echo wp_kses_post( $message ); ?>
	</div>
</div><!-- End .frm-quizzes-show-score -->
