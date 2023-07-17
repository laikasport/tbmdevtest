<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 5.3
 */
class FrmViewsPaginationController {

	/**
	 * Load a page of a View via an AJAX Request.
	 *
	 * @return void
	 */
	public static function load_page() {
		$id = FrmViewsPageState::get_from_request( 'id', 0 );
		if ( ! $id ) {
			// If the state did not validate, the request token is invalid.
			wp_die( -1 );
		}

		$id      = absint( $id );
		$options = self::get_options( $id );
		if ( ! is_array( $options ) || empty( $options['ajax_pagination'] ) ) {
			wp_die( -1 );
		}

		$atts = FrmViewsPageState::get_shortcode_atts_from_request();
		if ( ! empty( $atts['wpautop'] ) ) {
			remove_filter( 'the_content', 'wpautop' );
		}

		if ( FrmViewsPageState::get_from_request( 'load_more', 0 ) ) {
			self::add_filter_to_remove_before_after_content( $id );
		}

		$graph_offset = FrmAppHelper::get_post_param( 'frm_graph_offset', 0, 'absint' );
		if ( $graph_offset ) {
			add_filter(
				'frm_graph_id',
				function( $graph_id, $args ) use ( $graph_offset ) {
					return '_frm_' . strtolower( $args['type'] ) . ( $args['graph_auto_id'] + $graph_offset );
				},
				10,
				2
			);
		}

		$view = get_post( $id );
		if ( ! ( $view instanceof WP_Post ) || 'frm_display' !== $view->post_type ) {
			wp_die( -1 );
		}

		if ( $view instanceof WP_Post ) {
			self::maybe_overwrite_view_link( $view );
		}

		self::maybe_offset_modals();

		$html = FrmViewsDisplaysController::get_shortcode( $atts );
		echo wp_json_encode(
			array(
				'html'   => $html,
				'graphs' => self::get_google_graphs(),
				'modal'  => self::get_modal_html(),
			)
		);

		wp_die();
	}

	/**
	 * Make sure that the pretty URL for the view uses the original URL and not the AJAX endpoint URL.
	 * Otherwise [detaillink] shortcodes will not work properly.
	 *
	 * @since 5.3.3
	 *
	 * @param WP_Post $view
	 * @return void
	 */
	private static function maybe_overwrite_view_link( $view ) {
		if ( false === strpos( $view->post_content, '[detaillink]' ) ) {
			// This is only required when there is a detail link. Otherwise don't bother with it.
			return;
		}

		$site_url = FrmViewsPageState::get_from_request( 'site_url', '' );
		if ( ! $site_url ) {
			return;
		}

		// Carry over global post object so detail page links work on pages with embedded views.
		$global_post_id = FrmViewsPageState::get_from_request( 'global_post', '' );
		$global_post    = $global_post_id && is_numeric( $global_post_id ) ? get_post( $global_post_id ) : false;

		if ( ! $global_post ) {
			$global_post = $view;
		}

		FrmViewsAppHelper::force_view_as_post_global( $global_post );

		add_filter(
			'post_link',
			function( $permalink, $post ) use ( $view, $site_url ) {
				if ( $view->ID === $post->ID ) {
					return $site_url;
				}
				return $permalink;
			},
			10,
			2
		);
	}

	/**
	 * Maybe offset the modal index so new modals don't conflict with existing ones.
	 *
	 * @since 5.3.2
	 *
	 * @return void
	 */
	private static function maybe_offset_modals() {
		global $frm_vars;

		$modal_offset = FrmAppHelper::get_post_param( 'frm_modal_offset', 0, 'absint' );
		if ( ! $modal_offset ) {
			return;
		}

		if ( ! isset( $frm_vars['modals'] ) || ! is_array( $frm_vars['modals'] ) ) {
			$frm_vars['modals'] = array();
		}

		// Fill the modals global with placeholder 0 values. These get removed later in self::get_modal_html.
		$count = count( $frm_vars['modals'] );
		while ( $count < $modal_offset ) {
			$frm_vars['modals'][] = 0;
			++$count;
		}
	}

	/**
	 * Get any modals rendered from the formidable-modal add on.
	 *
	 * @since 5.3.2
	 *
	 * @return string
	 */
	private static function get_modal_html() {
		global $frm_vars;

		if ( ! is_callable( 'frmBtsModApp::output_modal' ) || empty( $frm_vars['modals'] ) ) {
			return '';
		}

		// Remove 0s from modal offset before printing modals.
		$frm_vars['modals'] = array_filter(
			$frm_vars['modals'],
			function( $modal ) {
				return 0 !== $modal;
			}
		);

		ob_start();
		frmBtsModApp::output_modal();
		return ob_get_clean();
	}

	/**
	 * @return array
	 */
	private static function get_google_graphs() {
		global $frm_vars;
		return ! empty( $frm_vars['google_graphs'] ) ? $frm_vars['google_graphs'] : array();
	}

	/**
	 * When loading more we only want to load the inner content, not the before or after content.
	 *
	 * @param int $view_id
	 */
	private static function add_filter_to_remove_before_after_content( $view_id ) {
		add_filter(
			'frm_filter_view',
			function( $view ) use ( $view_id ) {
				if ( $view_id !== $view->ID ) {
					return $view;
				}

				if ( FrmViewsDisplaysHelper::is_legacy_table_type( $view_id ) ) {
					// Do not strip before/after content for legacy tables as it's easier to parse the full table.
					return $view;
				}

				if ( self::view_opens_in_before_content_and_closes_in_after_content( $view ) ) {
					// Set a flag so this can still be tracked after the before and after content are cleared.
					$view->is_container_type = true;
				}

				$view->frm_before_content = '';
				$view->frm_after_content  = '';

				return $view;
			}
		);
	}

	/**
	 * Get the pagination HTML for a paginated View
	 *
	 * @param object $view
	 * @param int    $total_count
	 * @return string
	 */
	public static function setup_pagination( $view, $total_count ) {
		$pagination = '';

		if ( is_int( $view->frm_page_size ) ) {
			$current_page = FrmViewsDisplaysHelper::get_current_page_num( $view->ID );
			$pagination   = self::get_pagination( $view, $total_count, $current_page );
		}

		return $pagination;
	}

	/**
	 * Get View pagination
	 *
	 * @param object $view
	 * @param int    $record_count
	 * @param int    $current_page
	 * @return string
	 */
	private static function get_pagination( $view, $record_count, $current_page ) {
		$page_count = FrmEntry::getPageCount( $view->frm_page_size, $record_count );

		if ( $page_count <= 1 ) {
			return '';
		}

		if ( ! is_numeric( $current_page ) ) {
			$page_param   = 'frm-page-' . $view->ID;
			$current_page = FrmAppHelper::get_param( $page_param, 1, 'get', 'absint' );
		}

		$options         = self::get_options( $view->ID );
		$ajax_pagination = is_array( $options ) && ! empty( $options['ajax_pagination'] );

		$load_more       = $ajax_pagination && 'load-more' === $options['ajax_pagination'];
		$infinite_scroll = $ajax_pagination && 'infinite-scroll' === $options['ajax_pagination'];

		if ( $ajax_pagination ) {
			self::add_ajax_pagination_container_filter( $view->ID );
		}

		$page_last_record  = FrmAppHelper::get_last_record_num( $record_count, $current_page, $view->frm_page_size );
		$page_first_record = FrmAppHelper::get_first_record_num( $record_count, $current_page, $view->frm_page_size );
		$page_param        = 'frm-page-' . $view->ID;
		$args              = compact( 'current_page', 'record_count', 'page_count', 'page_last_record', 'page_first_record', 'page_param', 'view' );

		$pagination = '';
		if ( $load_more || $infinite_scroll ) {
			$show_load_more = $current_page < $page_count;
			if ( $show_load_more ) {
				$pagination = self::get_load_more( $view, $current_page, $infinite_scroll, $args );
			}
		} else {
			$pagination = FrmAppHelper::get_file_contents( FrmViewsAppHelper::plugin_path() . '/classes/views/displays/pagination.php', $args );
		}

		if ( $ajax_pagination && ! wp_doing_ajax() ) {
			self::add_ajax_pagination_scripts();
		}

		return $pagination;
	}

	/**
	 * @param int $view_id
	 * @return void
	 */
	private static function add_ajax_pagination_container_filter( $view_id ) {
		add_filter(
			'frm_pagination_class',
			function( $class, $atts ) use ( $view_id ) {
				if ( $view_id !== $atts['view']->ID ) {
					return $class;
				}
				return $class . ' frm_ajax_pagination_cont';
			},
			10,
			2
		);
	}

	/**
	 * Get HTML for Load More button.
	 *
	 * @param object $view
	 * @param int    $current_page
	 * @param bool   $infinite_scroll
	 * @param array  $args
	 * @return string
	 */
	private static function get_load_more( $view, $current_page, $infinite_scroll, $args ) {
		$view_id = $view->ID;

		$settings = FrmViewsAppHelper::get_settings();
		$atts     = array(
			'href'        => '#',
			'class'       => 'frm-ajax-pagination-load-more',
			'frm-page'    => $current_page,
			'frm-view-id' => $view_id,
		);

		if ( FrmViewsDisplaysHelper::is_table_type( $view ) || FrmViewsDisplaysHelper::is_legacy_table_type( $view->ID ) ) {
			$atts['class'] .= ' frm-table-type';
		} elseif ( FrmViewsDisplaysHelper::is_grid_type( $view ) ) {
			$atts['class'] .= ' frm-grid-type';
		} elseif ( self::view_opens_in_before_content_and_closes_in_after_content( $view ) ) {
			$atts['class'] .= ' frm-container-type';
		}

		if ( $infinite_scroll ) {
			$atts['class'] .= ' frm-infinite-scroll';
		}

		$load_more  = '<div class="' . esc_attr( apply_filters( 'frm_pagination_class', 'frm_pagination_cont', $args ) ) . '">';
		$load_more .= '<a ' . FrmAppHelper::array_to_html_params( $atts ) . '>' . esc_html( $settings->load_more_button_text ) . '</a>';
		$load_more .= '</div>';

		return $load_more;
	}

	/**
	 * @param object $view
	 * @return bool
	 */
	private static function view_opens_in_before_content_and_closes_in_after_content( $view ) {
		if ( ! empty( $view->is_container_type ) ) {
			return true;
		}

		$before_content_opens_without_close = false !== strpos( $view->frm_before_content, '<div' ) && false === strpos( $view->frm_before_content, '</div>' );
		$after_content_closes_without_open  = false === strpos( $view->frm_after_content, '<div' ) && false !== strpos( $view->frm_after_content, '</div>' );
		return $before_content_opens_without_close && $after_content_closes_without_open;
	}

	/**
	 * Get meta options for a view.
	 *
	 * @param int $view_id
	 * @return mixed
	 */
	private static function get_options( $view_id ) {
		$options = get_post_meta( $view_id, 'frm_options', true );
		FrmAppHelper::unserialize_or_decode( $options );
		return $options;
	}

	/**
	 * Add scripts for AJAX pagination.
	 *
	 * @return void
	 */
	private static function add_ajax_pagination_scripts() {
		wp_register_script( 'frm_views_pagination', FrmViewsAppHelper::plugin_url() . '/js/pagination' . FrmViewsAppHelper::js_suffix() . '.js', array(), FrmViewsAppHelper::plugin_version(), true );
		$js_vars = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'frm_views_pagination', 'frmViewsPaginationVars', $js_vars );
		wp_enqueue_script( 'frm_views_pagination' );
	}

	/**
	 * Add an HTML comment in front of the view so AJAX pagination has a reference for what needs to be replaced.
	 * Used for regular AJAX pagination (not the load more button).
	 *
	 * @param string $content
	 * @param object $view
	 * @param string $show_count
	 * @return string
	 */
	public static function before_display_content( $content, $view, $show_count ) {
		if ( ! self::should_add_comment_marker( $view, $show_count ) || ! self::view_includes_comment_marker_at_top( $view ) ) {
			return $content;
		}

		return self::get_comment_marker( $view->ID ) . $content;
	}

	/**
	 * Only add the HTML comment before the view for regular pagination.
	 * Unless it's a table or grid type, then always show it at the top.
	 *
	 * @param object $view
	 * @return bool
	 */
	private static function view_includes_comment_marker_at_top( $view ) {
		if ( is_numeric( $view->frm_ajax_pagination ) ) {
			return true;
		}
		return FrmViewsDisplaysHelper::is_table_type( $view ) || FrmViewsDisplaysHelper::is_grid_type( $view ) || FrmViewsDisplaysHelper::is_legacy_table_type( $view->ID ) || self::view_opens_in_before_content_and_closes_in_after_content( $view );
	}

	/**
	 * Add an HTML comment before the after content for reference on where to add more content.
	 * Used for the load more button (not regular pagination).
	 *
	 * @param string $content
	 * @param object $view
	 * @param string $show_count
	 * @return string
	 */
	public static function after_display_content( $content, $view, $show_count ) {
		if ( ! self::should_add_comment_marker( $view, $show_count ) || self::view_includes_comment_marker_at_top( $view ) ) {
			return $content;
		}

		return self::get_comment_marker( $view->ID ) . $content;
	}

	/**
	 * @param object $view
	 * @param string $show_count
	 * @return bool
	 */
	private static function should_add_comment_marker( $view, $show_count ) {
		return 'all' === $show_count && ! empty( $view->frm_ajax_pagination );
	}

	/**
	 * Get an HTML comment to place at the beginning of a view for reference when replacing page content.
	 *
	 * @param int $view_id
	 * @return string
	 */
	public static function get_comment_marker( $view_id ) {
		FrmViewsPageState::set_initial_value( 'id', $view_id );
		$state = FrmViewsPageState::maybe_get_state_string();
		return '<!-- FRM-VIEW ' . absint( $view_id ) . ' state="' . esc_attr( $state ) . '" -->';
	}
}
