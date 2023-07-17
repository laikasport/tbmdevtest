<?php
/**
 * Class FrmQuizzesTableHTMLGenerator
 *
 * @package FrmQuizzes
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

/**
 * Class FrmQuizzesTableHTMLGenerator
 */
class FrmQuizzesTableHTMLGenerator extends FrmTableHTMLGenerator {

	/**
	 * Generate a two cell row for an HTML table
	 *
	 * @since 2.0
	 *
	 * @param string $label The label.
	 * @param string $value The value.
	 *
	 * @return string
	 */
	public function generate_two_cell_table_row( $label, $value ) {
		add_filter( 'wp_kses_allowed_html', array( &$this, 'allow_extra_html' ) );
		$row = parent::generate_two_cell_table_row( $label, $value );
		remove_filter( 'wp_kses_allowed_html', array( &$this, 'allow_extra_html' ) );
		return $row;
	}

	/**
	 * Since the correct icon wsa added in the HTML, show it.
	 *
	 * @param array $tags
	 * @since 2.0
	 */
	public function allow_extra_html( $tags ) {
		$tags['svg'] = array(
			'class'       => true,
			'id'          => true,
			'xmlns'       => true,
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'style'       => true,
			'fill'        => true,
			'aria-label'  => true,
			'aria-hidden' => true,
		);

		$tags['path']  = array(
			'd'        => true,
		);

		$tags['input'] = array(
			'type'     => 'text',
			'value'    => true,
		);

		$tags['button'] = array(
			'type'          => 'button',
			'class'         => true,
			'data-field-id' => true,
			'data-entry-id' => true,
			'style'         => true,
		);

		return $tags;
	}
}
