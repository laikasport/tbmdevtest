<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesHooksController {

	/**
	 * @return void
	 */
	public static function load_hooks() {
		add_filter( 'frm_get_field_type_class', 'FrmQuizzesAppController::add_field_class', 10, 2 );

		add_action( 'frm_registered_form_actions', 'FrmQuizzesSettingsController::register_actions' );
		add_filter( 'frm_get_paged_fields', 'FrmQuizzesAppController::change_fields_order', 9, 2 );
		add_filter( 'frm_entry_values_fields', 'FrmQuizzesAppController::change_fields_order', 10, 2 );
		add_filter( 'frm_fields_in_entries_list_table', 'FrmQuizzesAppController::set_score_order' );
		add_action( 'frm_trigger_quiz_action', 'FrmQuizzesSettingsController::calculate_quiz_score', 10, 3 );
		add_action( 'frm_trigger_quiz_outcome_action', 'FrmQuizzesSettingsController::calculate_quiz_score', 10, 3 );
		add_action( 'frm_after_create_entry', 'FrmQuizzesSettingsController::calculate_score_when_create_entry', 10, 2 );
		add_action( 'frm_after_update_entry', 'FrmQuizzesSettingsController::calculate_score_when_update_entry', 10, 2 );
		add_filter( 'frm_graph_value', 'FrmQuizzesEntriesController::graph_value', 10, 2 );

		if ( FrmQuizzesMigrationController::migrated_to_v2() ) {
			add_filter( 'frm_entry_formatter_class', 'FrmQuizzesEntriesController::change_entry_formatter_class', 10, 2 );
			add_filter( 'frm_main_feedback', 'FrmQuizzesScoredController::get_success_message', 10, 3 );
			add_filter( 'frm_main_feedback', 'FrmQuizzesOutcomeController::get_success_message', 10, 3 );
		} else {
			add_action( 'frm_after_create_entry', 'FrmQuizzesAppController::calculate_quiz_score', 20, 2 );
			add_action( 'frm_after_update_entry', 'FrmQuizzesAppController::calculate_quiz_score', 20, 2 );
		}

		self::load_admin_hooks();
		self::load_ajax_hooks();
	}

	/**
	 * @return void
	 */
	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', 'FrmQuizzesAppController::include_updater' );
		add_action( 'admin_enqueue_scripts', 'FrmQuizzesAppController::add_scripts' );
		add_filter( 'frm_pro_available_fields', 'FrmQuizzesAppController::add_field' );
		add_filter( 'frm_fields_in_form_builder', 'FrmQuizzesAppController::hide_builder_field' );
		add_action( 'after_plugin_row_formidable-quizzes/formidable-quizzes.php', 'FrmQuizzesAppController::min_version_notice' );
		add_action( 'frm_add_settings_section', 'FrmQuizzesSettingsController::add_settings_section' );
		add_filter( 'frm_entries_column_value', 'FrmQuizzesEntriesController::entries_column_value', 10, 2 );
		add_filter( 'frm_importing_xml', 'FrmQuizzesXMLController::importing_xml', 9, 2 );
		add_filter( 'frm_after_import_view', 'FrmQuizzesXMLController::after_import_post', 10, 2 );
		add_action( 'frm_xml_export_before_types_loop', 'FrmQuizzesXMLController::on_export_before_types_loop' );
		add_filter( 'frm_action_logic_exclude_fields', 'FrmQuizzesOutcomeController::hide_quiz_score_from_quiz_outcome_condition_logic_row', 10, 2 );
		add_filter( 'frm_create_field_value_selector', 'FrmQuizzesFieldFactory::create_field_value_selector', 11, 3 );

		if ( ! FrmQuizzesMigrationController::migrated_to_v2() ) {
			add_action( 'admin_notices', 'FrmQuizzesMigrationController::show_notice' );
			add_action( 'frm_add_form_perm_options', 'FrmQuizzesFormSettings::add_setting', 30 );
		}
	}

	/**
	 * @return void
	 */
	public static function load_ajax_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'wp_ajax_frm_quizzes_migrate', 'FrmQuizzesMigrationController::ajax_migrate' );
		add_action( 'wp_ajax_frm_quizzes_set_manual_score', 'FrmQuizzesScoredController::ajax_set_manual_score' );
	}
}
