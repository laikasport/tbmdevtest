<?php
/**
 * Handle converting HTML to PDF
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

use Dompdf\Dompdf;

/**
 * Class FrmPdfsHtmlToPdf
 */
class FrmPdfsHtmlToPdf {

	/**
	 * HTML to PDF converter.
	 *
	 * @var object
	 */
	protected $converter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Dompdf\Dompdf', false ) ) {
			require_once FrmPdfsAppHelper::plugin_path() . '/classes/lib/dompdf/autoload.inc.php';
		}

		$upload_dir = wp_upload_dir();

		$dompdf_args = array(
			'enable_remote'       => true,
			'enable_html5_parser' => true,
			'temp_dir'            => get_temp_dir(),
			'font_dir'            => $upload_dir['basedir'],
			'enable_font_subsetting' => true,
			'http_context'        => array(
				'ssl'  => array(
					'verify_peer' => true,
				),
				'http' => array(
					'header' => 'Referer: ' . esc_url( home_url() ) . "\r\n",
				),
			),
		);

		/**
		 * Filters the args passed to the DOMPDF constructor.
		 *
		 * @param array $dompdf_args DOMPDF args.
		 */
		$dompdf_args = apply_filters( 'frm_pdfs_dompdf_args', $dompdf_args );

		$this->converter = new Dompdf( $dompdf_args );
	}

	/**
	 * Loads an HTML string
	 * Parse errors are stored in the global array _dompdf_warnings.
	 *
	 * @param string $html     HTML text to load.
	 * @param string $encoding Encoding of $str.
	 * @return void
	 */
	public function load_html( $html, $encoding = null ) {
		$this->converter->loadHtml( $html, $encoding );
	}

	/**
	 * Set the paper size. Can be 'letter', 'legal', 'A4', etc.
	 * Orientation can be 'portrait' or 'landscape'.
	 *
	 * @since 2.0
	 * @param array $args Paper arguments including 'paper_size' and 'orientation'.
	 * @return void
	 */
	public function set_paper( $args ) {
		$paper_size  = isset( $args['paper_size'] ) ? $args['paper_size'] : 'letter';
		$orientation = isset( $args['orientation'] ) ? $args['orientation'] : 'portrait';
		$this->converter->setPaper( $paper_size, $orientation );
	}

	/**
	 * Renders the HTML to PDF.
	 *
	 * @return void
	 */
	public function render() {
		$this->converter->render();
	}

	/**
	 * Streams the PDF to the client.
	 *
	 * The file will open a download dialog by default. The options
	 * parameter controls the output. Accepted options (array keys) are:
	 *
	 * 'compress' = > 1 (=default) or 0:
	 *   Apply content stream compression
	 *
	 * 'Attachment' => 1 (=default) or 0:
	 *   Set the 'Content-Disposition:' HTTP header to 'attachment'
	 *   (thereby causing the browser to open a download dialog)
	 *
	 * @param string $filename The name of the streamed file.
	 * @param array  $options  Header options (see above).
	 * @return void
	 */
	public function stream( $filename, $options = array() ) {
		$this->converter->stream( $filename, $options );
	}

	/**
	 * Returns the PDF as a string.
	 *
	 * The options parameter controls the output. Accepted options are:
	 *
	 * 'compress' = > 1 or 0 - apply content stream compression, this is
	 *    on (1) by default
	 *
	 * @param array $options options (see above).
	 *
	 * @return string|null
	 */
	public function output( $options = array() ) {
		return $this->converter->output( $options );
	}
}
