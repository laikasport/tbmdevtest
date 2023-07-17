<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmQuizzesUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'Quiz Maker';
	public $download_id = 20815759;
	public $version;

	public function __construct() {
		$this->plugin_file = FrmQuizzesAppController::path() . '/formidable-quizzes.php';
		$this->version     = FrmQuizzesAppController::$plug_version;
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmQuizzesUpdate();
	}
}
