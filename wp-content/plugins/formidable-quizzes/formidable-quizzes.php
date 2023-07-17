<?php
/**
 * Plugin Name: Formidable Quiz Maker
 * Description: Make quizes, automatically score them and show user scores
 * Version: 3.1
 * Plugin URI: https://formidableforms.com/
 * Author URI: https://formidableforms.com/
 * Author: Strategy11
 *
 * @package formidable-quizzes
 */

// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

function frm_quizzes_forms_autoloader( $class_name ) {
	$path = dirname( __FILE__ );

	// Only load Frm classes here.
	if ( ! preg_match( '/^FrmQuiz.+$/', $class_name ) ) {
		return;
	}

	if ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$path .= '/controllers/' . $class_name . '.php';
	} elseif ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$path .= '/helpers/' . $class_name . '.php';
	} else {
		$path .= '/models/' . $class_name . '.php';
	}

	if ( file_exists( $path ) ) {
		include $path;
	}
}

// Add the autoloader.
spl_autoload_register( 'frm_quizzes_forms_autoloader' );

// Load hooks.
FrmQuizzesHooksController::load_hooks();
