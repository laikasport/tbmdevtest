<?php

namespace Barn2\Plugin\Document_Library_Pro\Admin;

use Barn2\DLP_Lib\Registerable,
	Barn2\DLP_Lib\Service,
	Barn2\DLP_Lib\Conditional,
	Barn2\DLP_Lib\Util;

defined( 'ABSPATH' ) || exit;

/**
 * Handles functionality on the Documents Edit and New Document screens
 *
 * @package   Barn2/document-library-pro
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Document_Edit implements Registerable, Service, Conditional {

	/**
	 * {@inheritdoc}
	 */
	public function is_required() {
		return Util::is_admin();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		// Default Metaboxes
		add_filter( 'default_hidden_meta_boxes', [ $this, 'hide_author_metabox' ], 10, 2 );
	}

	/**
	 * Hide the author column by default.
	 *
	 * @param array $hidden The list of hidden columns.
	 * @param \WP_Screen $screen The current screen.
	 * @return array The list of hidden columns.
	 */
	public function hide_author_metabox( $hidden, $screen ) {
		if ( $screen &&  'post' === $screen->base && 'dlp_document' === $screen->id ) {
			$hidden[] = 'authordiv';
		}

		return $hidden;
	}

}
