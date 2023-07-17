<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

/**
 * @since 3.0
 */
class FrmQuizzesXMLController {

	/**
	 * @var array<int,int>|null
	 */
	private static $imported_posts;

	/**
	 * Hook into frm_importing_xml filter early to access the value of $imported before FrmProXmlController::importing_xml runs.
	 *
	 * @since 3.0
	 *
	 * @param array            $imported
	 * @param SimpleXMLElement $xml
	 * @return array
	 */
	public static function importing_xml( $imported, $xml ) {
		if ( isset( $xml->item ) && ! empty( $imported['posts'] ) ) {
			self::$imported_posts = $imported['posts'];
			add_filter( 'frm_import_val', array( __CLASS__, 'filter_import_value' ), 10, 2 );
		}
		return $imported;
	}

	/**
	 * Update imported quiz outcome IDs on import to reflect the new Action IDs.
	 *
	 * @since 3.0
	 *
	 * @param mixed    $value
	 * @param stdClass $field
	 * @return mixed
	 */
	public static function filter_import_value( $value, $field ) {
		if ( 'quiz_score' !== $field->type || ! isset( self::$imported_posts ) || ! isset( self::$imported_posts[ $value ] ) ) {
			return $value;
		}
		return self::$imported_posts[ $value ];
	}

	/**
	 * Hook in before posts are exported.
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	public static function on_export_before_types_loop() {
		add_filter( 'the_post', array( __CLASS__, 'filter_post_on_export' ) );
	}

	/**
	 * The purpose of this is to filter the exported post content for outcomes to include the image src.
	 *
	 * @since 3.1
	 *
	 * @param WP_Post $post
	 * @return WP_Post
	 */
	public static function filter_post_on_export( $post ) {
		if ( self::should_filter_post_on_export( $post ) ) {
			self::maybe_add_image_src_filter( $post );
		}
		return $post;
	}

	/**
	 * @since 3.1
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	private static function maybe_add_image_src_filter( $post ) {
		$raw_post_content = $post->post_content;
		FrmAppHelper::unserialize_or_decode( $raw_post_content );

		if ( ! is_array( $raw_post_content ) || empty( $raw_post_content['image'] ) || ! is_numeric( $raw_post_content['image'] ) ) {
			return;
		}

		add_filter(
			'the_content_export',
			/**
			 * @param string  $post_content
			 * @param WP_Post $post
			 * @param array   $raw_post_content
			 * @return string
			 */
			function( $post_content ) use ( $post, $raw_post_content ) {
				if ( $post_content === $post->post_content ) {
					$raw_post_content['src'] = wp_get_attachment_url( (int) $raw_post_content['image'] );
					return FrmAppHelper::prepare_and_encode( $raw_post_content );
				}
				return $post_content;
			}
		);
	}

	/**
	 * Check post before modifying it. We only want to modify Quiz Outcomes form actions for now.
	 *
	 * @since 3.1
	 *
	 * @param WP_Post $post
	 * @return bool
	 */
	private static function should_filter_post_on_export( $post ) {
		if ( $post->post_type !== FrmFormActionsController::$action_post_type || FrmQuizzesFormActionHelper::$outcome_action_name !== $post->post_excerpt ) {
			// Only update quiz outcomes.
			return false;
		}

		if ( false === strpos( $post->post_content, 'image' ) ) {
			// Only filter an outcome with an image set. Otherwise we don't need to.
			return false;
		}

		return true;
	}

	/**
	 * Import images for outcomes from src URL if available.
	 *
	 * @since 3.1
	 *
	 * @param int   $post_id
	 * @param array $post
	 * @return void
	 */
	public static function after_import_post( $post_id, $post ) {
		if ( $post['post_type'] !== FrmFormActionsController::$action_post_type || FrmQuizzesFormActionHelper::$outcome_action_name !== $post['post_excerpt'] ) {
			return;
		}

		if ( false === strpos( $post['post_content'], 'image' ) ) {
			return;
		}

		$post_content = $post['post_content'];
		FrmAppHelper::unserialize_or_decode( $post_content );

		if ( ! is_array( $post_content ) || empty( $post_content['src'] ) ) {
			return;
		}

		// Fake a field object. As this is for outcomes, we don't need to pass a real field.
		$field_object          = new stdClass();
		$field_object->id      = 0;
		$field_object->type    = 'file';
		$field_object->form_id = $post['menu_order'];

		$image_id = FrmProFileImport::import_attachment( wp_unslash( $post_content['src'] ), $field_object );
		if ( is_numeric( $image_id ) ) {
			$post_content['image'] = $image_id;
		}

		unset( $post_content['src'] ); // Remove src from post content as it isn't needed after the attachment is imported.

		$post['ID']           = $post_id;
		$post['post_content'] = FrmAppHelper::prepare_and_encode( $post_content );

		wp_update_post( $post, false, false );
	}
}
