<?php
/**
 * App helper
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsAppHelper
 */
class FrmPdfsAppHelper {

	const ERROR_BAD_REQUEST = 400;

	const ERROR_FORBIDDEN = 403;

	const ERROR_NOT_FOUND = 404;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $plug_version = '2.0';

	/**
	 * Gets plugin folder name.
	 *
	 * @return string
	 */
	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	/**
	 * Gets plugin path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return dirname( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Gets plugin file path.
	 *
	 * @return string
	 */
	public static function plugin_file() {
		return self::plugin_path() . '/formidable-pdfs.php';
	}

	/**
	 * Gets plugin URL.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-pdfs.php' );
	}

	/**
	 * Gets plugin relative URL.
	 *
	 * @return string
	 */
	public static function relative_plugin_url() {
		return str_replace( array( 'https:', 'http:' ), '', self::plugin_url() );
	}

	/**
	 * Checks if current user can download pdf.
	 *
	 * @return bool
	 */
	public static function current_user_can_download_pdf() {
		return current_user_can( 'frm_view_entries' );
	}

	/**
	 * Increases server limitation for exporting file.
	 *
	 * @see FrmXMLController::csv()
	 */
	public static function increase_export_server_limit() {
		set_time_limit( 0 ); // Remove time limit to execute this function.
		$mem_limit = str_replace( 'M', '', ini_get( 'memory_limit' ) );
		if ( (int) $mem_limit < 256 ) {
			wp_raise_memory_limit();
		}
	}

	/**
	 * Checks if we can render images in PDF file.
	 *
	 * @return bool
	 */
	public static function can_render_images_in_pdf() {
		return ini_get( 'allow_url_fopen' );
	}

	/**
	 * Gets the array of incompatible error messages.
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public static function get_incompatible_error_messages_arr() {
		$error_messages = array();

		$php_version = '7.1';
		if ( version_compare( phpversion(), $php_version, '<' ) ) {
			$error_messages[] = '<p>' . sprintf(
				// translators: PHP version.
				__( 'Formidable PDFs requires at least PHP %s. Please update your PHP version.', 'formidable-pdfs' ),
				$php_version
			) . '</p>';
		}

		$frm_version = '5.4.2';
		if ( ! class_exists( 'FrmAppHelper', false ) || version_compare( FrmAppHelper::$plug_version, $frm_version, '<' ) ) {
			$error_messages[] = '<p>' . sprintf(
				// translators: Formidable Forms version.
				__( 'Formidable PDFs requires at least Formidable Forms %s.', 'formidable-pdfs' ),
				$frm_version
			) . '</p>';
		}

		if ( ! class_exists( 'FrmProDb', false ) || version_compare( FrmProDb::$plug_version, $frm_version, '<' ) ) {
			$error_messages[] = '<p>' . sprintf(
				// translators: Formidable Forms Pro version.
				__( 'Formidable PDFs requires at least Formidable Forms Pro %s.', 'formidable-pdfs' ),
				$frm_version
			) . '</p>';
		}

		$extensions = get_loaded_extensions();
		$ext_names  = array();
		if ( ! in_array( 'dom', $extensions, true ) ) {
			$ext_names[] = 'PHP DOM';
		}

		if ( ! in_array( 'mbstring', $extensions, true ) ) {
			$ext_names[] = 'PHP MBString';
		}

		if ( ! in_array( 'gd', $extensions, true ) ) {
			$ext_names[] = 'PHP GD';
		}

		if ( $ext_names ) {
			$error_messages[] = '<p>' . sprintf(
				// translators: PHP extensions.
				__( 'Formidable PDFs requires following extensions to be installed: %s', 'formidable-pdfs' ),
				implode( ', ', $ext_names )
			) . '</p>';
		}

		return $error_messages;
	}

	/**
	 * Check if the PDF is the default entry or not.
	 *
	 * @since 2.0
	 * @param array $params The values passed from a shortcode.
	 * @return bool
	 */
	public static function is_entry_table( $params ) {
		return empty( $params['view'] ) && empty( $params['source'] );
	}

	/**
	 * Checks if the PDF is the view.
	 *
	 * @since 2.0
	 *
	 * @param array $params The values passed from a shortcode.
	 * @return bool
	 */
	public static function is_view( $params ) {
		return ! empty( $params['view'] );
	}

	/**
	 * Checks if PDF file is being processing.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public static function is_pdf() {
		return FrmPdfsAppController::$is_processing;
	}

	/**
	 * Wraps the HTML content and CSS into a full HTML page.
	 *
	 * @since 2.0
	 *
	 * @param string $html HTML content.
	 * @param string $css  CSS content, includes `<style>` tag.
	 * @return string
	 */
	public static function wrap_html( $html, $css ) {
		$full_html = <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		$css
	</head>

	<body>
		$html
	</body>
</html>
HTML;
		return $full_html;
	}
}
