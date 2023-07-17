<?php
/**
 * App controller
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsAppController
 */
class FrmPdfsAppController {

	const FILE_MODE = 'file';

	const DOWNLOAD_MODE = 'download';

	const VIEW_MODE = 'view';

	/**
	 * Flag to check if PDF file is being processed.
	 *
	 * @since 2.0
	 *
	 * @var bool
	 */
	public static $is_processing = false;

	/**
	 * Shows the incompatible notice.
	 */
	public static function show_incompatible_notice() {
		$error_message = self::get_incompatible_error_message();

		if ( ! $error_message ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<?php echo FrmAppHelper::kses( $error_message, array( 'a', 'br', 'span', 'p' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
	}

	/**
	 * Gets incompatible error message.
	 *
	 * @return string
	 */
	public static function get_incompatible_error_message() {
		$messages = FrmPdfsAppHelper::get_incompatible_error_messages_arr();
		if ( ! $messages ) {
			return '';
		}

		return implode( '', $messages );
	}

	/**
	 * Adds incompatible notice messages to the Frm message list.
	 *
	 * @since 2.0
	 *
	 * @param array $messages Message list.
	 * @return array
	 */
	public static function add_incompatible_notice_to_message_list( $messages ) {
		return FrmPdfsAppHelper::get_incompatible_error_messages_arr() + $messages;
	}

	/**
	 * Initializes plugin translation.
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'formidable-pdfs', false, FrmPdfsAppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Includes addon updater.
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmPdfsUpdate::load_hooks();
		}
	}

	/**
	 * Generates entry PDF export file.
	 *
	 * @param object|int $entry Entry object or ID.
	 * @param array      $args  See {@see FrmPdfsAppController::get_default_entry_pdf_args()}.
	 * @return string|false|void Return the file path if `$mode` is set to `file`. Return `false` if entry doesn't exist.
	 */
	public static function generate_entry_pdf( $entry, $args = array() ) {
		$args = wp_parse_args( $args, self::get_default_entry_pdf_args() );

		FrmPdfsAppHelper::increase_export_server_limit();

		$html = self::generate_pdf_html( $entry, $args );

		if ( false === $html ) {
			return false;
		}

		$html_to_pdf = new FrmPdfsHtmlToPdf();
		$html_to_pdf->load_html( $html );
		$html_to_pdf->set_paper( $args );
		$html_to_pdf->render();

		$file_name = self::get_entry_pdf_file_name( $entry, $args );

		if ( self::DOWNLOAD_MODE === $args['mode'] || self::VIEW_MODE === $args['mode'] ) {
			$html_to_pdf->stream( $file_name, array( 'Attachment' => self::DOWNLOAD_MODE === $args['mode'] ) );
			die();
		}

		$file_path = get_temp_dir() . $file_name;
		$output    = $html_to_pdf->output();

		$written_bytes = file_put_contents( $file_path, $output );
		if ( false === $written_bytes ) {
			return false;
		}
		return $file_path;
	}

	/**
	 * Generates PDF html.
	 *
	 * @param object|int $entry Entry object or ID.
	 * @param array      $args  See {@see FrmPdfsAppController::get_default_entry_pdf_args()}.
	 * @return string|false Return `false` if entry doesn't exist.
	 */
	private static function generate_pdf_html( $entry, $args ) {
		self::$is_processing = true;

		if ( FrmPdfsAppHelper::is_entry_table( $args ) ) {
			$html = self::get_entry_table( $entry, $args );
		} elseif ( ! empty( $args['view'] ) ) {
			$html = self::get_view_shortcode( $args );
		} else {
			$html = self::get_any_shortcode( $args );
		}

		self::$is_processing = false;

		if ( ! $html ) {
			return false;
		}
		self::maybe_remove_images( $html );

		/**
		 * Filters the HTML content of PDF entry export file.
		 *
		 * @param string $content The HTML content of PDF entry export file.
		 * @param array  $args    {
		 *     The args. See {@see FrmPdfsAppController::get_default_entry_pdf_args()}. The following items are also included:
		 *
		 *     @type object   $entry  Entry object.
		 *     @type object[] $fields Array of field objects.
		 * }
		 */
		$html = apply_filters( 'frm_pdfs_export_content', $html, $args );

		$html = FrmPdfsAppHelper::wrap_html( $html, self::get_pdf_css( $entry, $args ) );

		return $html;
	}

	/**
	 * Get the entry in a table.
	 *
	 * @param object|int $entry The entry used for this PDF.
	 * @param array      $args  The parameters for [frm-show-entry].
	 * @return string
	 */
	private static function get_entry_table( $entry, $args ) {
		FrmEntry::maybe_get_entry( $entry );
		if ( ! $entry ) {
			return false;
		}

		$fields = self::get_fields_for_export( $entry, $args );

		$args      = $args + compact( 'entry', 'fields' );
		$show_args = self::set_entry_args( $args );

		ob_start();
		include FrmPdfsAppHelper::plugin_path() . '/classes/views/pdf-entry.php';
		return ob_get_clean();
	}

	/**
	 * Prepare the shortcode parameters to use in
	 * FrmProEntriesController::show_entry_shortcode()
	 *
	 * @param array $show_args The parameters for [frm-show-entry].
	 * @return array
	 */
	private static function set_entry_args( $show_args ) {
		$use_image = FrmPdfsAppHelper::can_render_images_in_pdf();

		$show_args['format']        = 'pdf';
		$show_args['inline_style']  = false;
		$show_args['show_filename'] = ! $use_image; // Used to show file uploads.
		$show_args['show_image']    = $use_image;
		$show_args['size']          = 'thumbnail';
		$show_args['add_link']      = true;

		if ( empty( $show_args['include_extras'] ) ) {
			$show_args['include_extras'] = 'page, section';
		}

		if ( ! $use_image ) {
			add_filter( 'frm_show_entry_defaults', array( __CLASS__, 'show_entry_defaults' ) );
		}

		/**
		 * Filters the args of FrmProEntriesController::show_entry_shortcode() in the content of PDF entry export file.
		 *
		 * @param array $show_args {
		 *     The args. See {@see FrmPdfsAppController::get_default_entry_pdf_args()}. The following items are also included:
		 *
		 *     @type object   $entry  Entry object.
		 *     @type object[] $fields Array of field objects.
		 * }
		 */
		return apply_filters( 'frm_pdfs_show_args', $show_args );
	}

	/**
	 * Prep View shortcode parameters and generate the output.
	 *
	 * @param array $args The parameters for the shortcode.
	 * @return string
	 */
	private static function get_view_shortcode( $args ) {
		$args['source'] = 'display-frm-data';
		if ( ! empty( $args['id'] ) && is_callable( array( 'FrmViewsDisplay', 'getOne' ) ) ) {
			$view = FrmViewsDisplay::getOne( $args['view'] );
			if ( $view ) {
				$args[ $view->frm_param ] = $args['id'];
			}
		}
		$args['id'] = $args['view'];
		return self::get_any_shortcode( $args );
	}

	/**
	 * Use the "shortcode" parameter to show anything.
	 *
	 * @param array $args The parameters for the shortcode.
	 * @return string
	 */
	private static function get_any_shortcode( $args ) {
		$shortcode_atts = '';
		unset( $args['action'] );
		foreach ( $args as $name => $val ) {
			$shortcode_atts .= ' ' . esc_attr( $name ) . '="' . esc_attr( $val ) . '"';
		}
		return do_shortcode( '[' . esc_attr( $args['source'] ) . $shortcode_atts . ']' );
	}

	/**
	 * Strip images if they won't show in the PDF.
	 *
	 * @since 2.0
	 * @param string $html The generated HTML for the PDF.
	 * @return void
	 */
	private static function maybe_remove_images( &$html ) {
		if ( ! FrmPdfsAppHelper::can_render_images_in_pdf() ) {
			$html = preg_replace( '#<img.+?src="([^"]*)".*?/?>#i', '', $html );
		}
	}

	/**
	 * Allow the signature user_html=0 parameter to prevent signature images.
	 *
	 * @param array $defaults The default params to generate PDF.
	 * @return array
	 */
	public static function show_entry_defaults( $defaults ) {
		$defaults['use_html'] = 0;
		return $defaults;
	}

	/**
	 * Gets default entry PDF export args.
	 *
	 * @return array
	 */
	private static function get_default_entry_pdf_args() {
		/*
		 * Default is `file`, the PDF content will be written into a file.
		 * Set to `download` if you want to prompt the download dialog.
		 * Set to `view` to view the file on the current page.
		 */
		$mode = self::FILE_MODE;
		return array(
			'mode'           => $mode,
			'exclude_fields' => '', // Comma separated IDs of exclude fields.
			'include_extras' => '',
		);
	}

	/**
	 * Gets fields for exporting.
	 *
	 * @param object $entry Entry object.
	 * @param array  $args  Args.
	 * @return array
	 */
	private static function get_fields_for_export( $entry, $args ) {
		$fields = FrmField::get_all_for_form( $entry->form_id );
		self::remove_invisible_fields( $args, $fields );

		/**
		 * Filters the fields for PDF entry exporting.
		 *
		 * @param object[] $fields Array of field object.
		 * @param array    $args   {
		 *     The args.
		 *
		 *     @type object $entry Entry object.
		 * }
		 */
		$fields = apply_filters( 'frm_pdfs_fields_for_export', $fields, compact( 'entry' ) );

		return $fields;
	}

	/**
	 * Removes any fields hidden with visibility setting.
	 *
	 * @param array $args Args.
	 * @param array $fields All the fields to display.
	 * @return void
	 */
	private static function remove_invisible_fields( $args, &$fields ) {
		$include_invisible = ! empty( $args['include_extras'] ) && strpos( $args['include_extras'], 'admin_only' ) !== false;
		if ( $include_invisible ) {
			return;
		}

		foreach ( $fields as $index => $field ) {
			if ( ! FrmProFieldsHelper::is_field_visible_to_user( $field ) ) {
				unset( $fields[ $index ] );
			}
			unset( $field );
		}
	}
	/**
	 * Gets entry pdf export file name.
	 *
	 * @param object $entry Entry object.
	 * @param array  $args  The parameters from the shortcode.
	 * @return string
	 */
	private static function get_entry_pdf_file_name( $entry, $args ) {
		$file_name = empty( $args['filename'] ) ? '[form_name]-[date format="Y-m-d"]-[key]' : $args['filename'];
		$file_name = str_replace( array( '{', '}' ), array( '[', ']' ), $file_name );
		FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $file_name );

		if ( $entry && strpos( $file_name, '[' ) !== false ) {
			$form      = FrmForm::getOne( $entry->form_id );
			$file_name = self::maybe_replace_form_name_shortcodes( $file_name, $form );
			$file_name = apply_filters( 'frm_content', $file_name, $form, $entry );

			$args['form']      = $form;
			$args['form_name'] = $form->name; // For backward compatibility.
		} else {
			// Clear as fallback.
			$file_name = str_replace( '[form_name]', '', $file_name );
			$file_name = str_replace( '[key]', '', $file_name );
		}

		if ( empty( $file_name ) ) {
			$file_name = gmdate( 'Y-m-d' );
		}
		$file_name = sanitize_title( $file_name ) . '.pdf';

		if ( empty( $args['filename'] ) ) {
			$file_name = 'frm-' . $file_name;
		}

		$args['entry'] = $entry;

		/**
		 * Filters the PDF entry export file name.
		 *
		 * @param string $file_name The file name.
		 * @param array  $args      {
		 *     The shortcode attributes and following values:
		 *
		 *     @type object $entry Entry object.
		 *     @type object $form  Form object. You should check it exists before using.
		 * }
		 */
		return apply_filters( 'frm_pdfs_export_file_name', $file_name, $args );
	}

	/**
	 * This only needs to be here temporarily.
	 * Remove it around 10-2022.
	 *
	 * @param string              $string The file name.
	 * @param stdClass|string|int $form   The current form used for the PDF.
	 * @return string
	 */
	private static function maybe_replace_form_name_shortcodes( $string, $form ) {
		if ( ! is_callable( 'FrmFormsController::replace_form_name_shortcodes' ) ) {
			return $string;
		}
		return FrmFormsController::replace_form_name_shortcodes( $string, $form );
	}

	/**
	 * Deletes a file.
	 *
	 * @param string $file_path File path.
	 */
	public static function delete_file( $file_path ) {
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Gets CSS for the pdf file.
	 *
	 * @param object $entry Entry object.
	 * @param array  $args  The shortcode parameters.
	 * @return string CSS content, includes `<style>` tag.
	 */
	private static function get_pdf_css( $entry, $args ) {
		ob_start();

		$is_entry_table = FrmPdfsAppHelper::is_entry_table( $args );

		$frm_style = new FrmStyle( 'default' );
		$style     = $frm_style->get_one();
		$defaults  = FrmStylesHelper::get_settings_for_output( $style );

		if ( ! $is_entry_table ) {
			include FrmAppHelper::plugin_path() . '/css/custom_theme.css.php';
		}

		include FrmPdfsAppHelper::plugin_path() . '/css/pdf.css.php';

		if ( $is_entry_table ) {
			include FrmPdfsAppHelper::plugin_path() . '/css/pdf-entry-table.css.php';
		}

		$css = ob_get_clean();

		/**
		 * Filters the CSS of PDF entry export.
		 *
		 * @param string $css CSS code. This doesn't include style tag.
		 * @param array  $args {
		 *     The args.
		 *
		 *     @type object $entry Entry object.
		 * }
		 */
		$css = apply_filters( 'frm_pdfs_css', $css, compact( 'entry' ) );

		// Remove a bit of css bulk.
		$css = preg_replace( '/@[a-z-]*keyframes\b[^{]*({(?>[^{}]++|(?1))*})/', '', $css );

		// Replace relative paths with absolute paths for fonts.
		$css = str_replace( 'url(\'../fonts/s11-fp.', 'url(\'' . FrmAppHelper::plugin_url() . '/fonts/s11-fp.', $css );

		// The background-color for <tr> doesn't work, so we add background-color to <td>.
		$css = str_replace( '.frm-alt-table tr:nth-child(even) {', '.frm-alt-table tr:nth-child(even), .frm-alt-table tr:nth-child(even) td {', $css );

		return '<style type="text/css">' . $css . '</style>';
	}

	/**
	 * Changes entry formatter class.
	 *
	 * @param string $formatter_class Entry formatter class name.
	 * @param array  $atts            The attributes. See {@see FrmEntriesController::show_entry_shortcode()}.
	 * @return string
	 */
	public static function entry_formatter_class( $formatter_class, $atts ) {
		if ( isset( $atts['format'] ) && 'pdf' === $atts['format'] ) {
			return 'FrmPdfsEntryFormatter';
		}
		return $formatter_class;
	}
}
