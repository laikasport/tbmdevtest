<?php

namespace Barn2\Plugin\Document_Library_Pro\Integration;

use Barn2\DLP_Lib\Registerable;
use Barn2\DLP_Lib\Service;

/**
 * Handles integration with SearchWP
 *
 * @package   Barn2/document-library-pro
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class SearchWP implements Registerable, Service {

	private $args;
	private $applicable = false;

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		if ( ! defined( 'SEARCHWP_PREFIX' ) ) {
			return;
		}

		/**
		 * Disable SearchWP integration.
		 *
		 * @param bool $enable Whether to disable the integration.
		 */
		if ( apply_filters( 'document_library_pro_disable_searchwp_integration', false ) ) {
			return;
		}

		add_action( 'document_library_pro_before_posts_query', [ $this, 'handle_documents_query' ] );
		add_filter( 'document_library_pro_query_args', [ $this, 'handle_dlp_args' ], 99, 2 );
		add_filter( 'searchwp\native\args', [ $this, 'handle_searchwp_args' ], 100, 2 );

		add_filter( 'searchwp\entry\data', [ $this, 'add_document_download_index' ], 20, 2 );
		add_filter( 'searchwp\source\attribute\options', [ $this, 'add_admin_option' ], 20, 2 );
	}

	/**
	 * Determine if we should run the query through SearchWP
	 *
	 * @param Table_Query $query
	 */
	public function handle_documents_query( $query ) {
		$search_term = ! empty( $query->args->user_search_term ) ? $query->args->user_search_term : $query->args->search_term;
		$applicable  = apply_filters( 'searchwp\barn2\posts_table\applicable', ! empty( trim( $search_term ) ), $query );

		if ( $applicable ) {
			$this->applicable = true;
			add_filter( 'searchwp\native\force', '__return_true', 131 );
			add_filter( 'searchwp\native\strict', '__return_false', 131 );
			add_filter( 'searchwp\native\short_circuit', '__return_false', 999 );
		}
	}

	/**
	 * Store the args so we can pass them to the SearchWP query.
	 *
	 * @param array $query_args
	 * @param Table_Query $query
	 * @return array
	 */
	public function handle_dlp_args( $query_args, $query ) {
		$this->args = $query_args;

		return $query_args;
	}

	/**
	 * Run our query through SearchWP.
	 *
	 * @param array $args
	 * @param WP_Query $query
	 * @return array
	 */
	public function handle_searchwp_args( $args, $query ) {
		if ( $this->applicable ) {
			// Traditional pagination isn't used.
			add_filter(
				'searchwp\query\args',
				function ( $args ) {
					// There are two queries run, one for this page and one to get totals.
					// We need to customize the offset and per page for the table data
					// but set nopaging=true when trying to find the totals.
					if ( -1 != $args['per_page'] ) {
						$args['offset']   = isset( $_REQUEST['start'] ) ? absint( $_REQUEST['start'] ) : 0;
						$args['per_page'] = isset( $_REQUEST['length'] ) ? absint( $_REQUEST['length'] ) : 25;
					}

					return $args;
				},
				20
			);

			$args = apply_filters(
				'searchwp\barn2\posts_table\query\args',
				array_merge( $args, $this->args )
			);

			remove_filter( 'searchwp\native\force', '__return_true', 131 );
			remove_filter( 'searchwp\native\strict', '__return_false', 131 );
		}

		return $args;
	}

	/**
	 * Index PDF and document files which are attached to document posts and store as a custom field in SearchWP.
	 *
	 * @param array $data
	 * @param SearchWP\Entry $entry
	 * @return array
	 */
	public function add_document_download_index( $data, \SearchWP\Entry $entry ) {
		if ( 'post.dlp_document' !== $entry->get_source()->get_name() ) {
			return $data;
		}

		$document = dlp_get_document( $entry->get_id() );

		if ( ! $document ) {
			return $data;
		}

		$file_id = $document->get_file_id();

		if ( ! $file_id ) {
			return $data;
		}

		$content = \SearchWP\Document::get_content( get_post( $file_id ) );
		$data['meta']['dlp_document_download'] = \SearchWP\Utils::tokenize( $content );

		return $data;
	}

	/**
	 * Add admin UI option for Document Download indexing.
	 *
	 * @param array $keys
	 * @param array $args
	 * @return array
	 */
	public function add_admin_option( $keys, $args ) {
		if ( $args['attribute'] !== 'meta' ) {
			return $keys;
		}

		$content_key = 'dlp_document_download';

		if ( ! in_array(
			$content_key,
			array_map( function( $option ) { return $option->get_value(); }, $keys )
		) ) {
			$keys[] = new \SearchWP\Option( $content_key, __( 'Document Library Pro: Document Downloads', 'document-library-pro' ) );
		}

		return $keys;
	}
}
