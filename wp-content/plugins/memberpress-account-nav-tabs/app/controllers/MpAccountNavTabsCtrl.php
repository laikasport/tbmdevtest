<?php
if ( ! defined( 'ABSPATH' ) ) {
  die( 'You are not allowed to call this page directly.' );
}

class MpAccountNavTabsCtrl extends MeprBaseCtrl {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Load hooks.
   *
   * @return void
   */
  public function load_hooks() {
    add_filter( 'mepr_view_paths', array( $this, 'add_view_path' ) );
    add_action( 'mepr_display_account_options', array( $this, 'display_options' ) );
    add_action( 'mepr-process-options', array( $this, 'save_options' ) );
    add_action( 'wp_ajax_add_new_tab_form', array( $this, 'add_new_tab_form' ) );

    // Enqueue scripts
    add_action( 'mepr-options-admin-enqueue-script', array( $this, 'admin_enqueue_options_scripts' ) );

    // Front end
    add_action( 'mepr_account_nav', array( $this, 'display_nav_tabs' ) );
    add_action( 'mepr_account_nav_content', array( $this, 'display_nav_tab_content' ) );

    // BuddyPress + MemberPress
    add_action( 'bp_setup_nav', array( $this, 'setup_bp_nav' ), 11 );
    add_action( 'plugins_loaded', array( $this, 'maybe_redirect_bp_tabs' ) );

  }

  /**
   * Add plugin path to memberpress view path
   *
   * @param mixed $paths MemberPress paths
   *
   * @return mixed
   */
  function add_view_path( $paths ) {
    array_splice( $paths, 1, 0, MACCONTNAVTABS_APP . 'views' );

    return $paths;
  }

  public function display_options() {
    $is_enabled = get_option( 'mepr_account_nav_tabs_enabled', false );
    $no_tabs    = true;
    MeprView::render( '/admin/options/account-nav-tabs', get_defined_vars() );
  }

  public function save_options( $params ) {
    update_option( 'mepr_account_nav_tabs_enabled', (int) ( isset( $params['mepr_account_nav_tabs_enabled'] ) ) );

    $tabs = array();

    if ( isset( $_POST['mepr_account_nav_tab'] ) ) {
      foreach ( $_POST['mepr_account_nav_tab'] as $tab ) {
        if ( $tab['type'] == 'url' && empty( $tab['url'] ) ) {
          continue;
        }
        if ( $tab['type'] == 'content' && empty( $tab['content'] ) ) {
          continue;
        }

        $tabs[] = array(
          'title'   => stripslashes( $tab['title'] ),
          'type'    => stripslashes( $tab['type'] ),
          'url'     => stripslashes( $tab['url'] ),
          'new_tab' => ( isset( $tab['new_tab'] ) ) ? 1 : 0,
          'content' => stripslashes( $tab['content'] )
        );
      }
    }
      update_option( 'mepr_account_nav_tabs', $tabs );
  }

  public function admin_enqueue_options_scripts( $hook ) {
    wp_enqueue_style( 'mp-accountnavtabs-options-css', MACCONTNAVTABS_URL . '/css/options.css', array() );
    $helpers = array(
      'wpnonce'       => wp_create_nonce( MACCONTNAVTABS_SLUG ),
      'confirmDelete' => __( 'Are you sure you want to delete this tab?', 'memberpress-account-nav-tabs' ),
    );
    wp_enqueue_script( 'mp-accountnavtabs-options-js', MACCONTNAVTABS_URL . '/js/options.js' );
    wp_localize_script( 'mp-accountnavtabs-options-js', 'MeprAccountNavTabs', $helpers );
  }

  public static function get_tabs() {
    $tabs       = array();
    $saved_tabs = get_option( 'mepr_account_nav_tabs', false );
    if ( $saved_tabs === false ) {
      return false;
    }
    foreach ( $saved_tabs as $tab ) {
      if ( ! is_object( $tab ) ) {
        $tabs[] = (object) $tab;
      } else {
        $tabs[] = $tab;
      }
    }

    return $tabs;
  }

  public function add_new_tab_form() {
    ob_start();
    $random_id = (int) rand( 100, 100000 );
    MpAccountNavTabsHelper::render_tab( $random_id, __( 'Tab Title', 'memberpress-account-nav-tabs' ), 'content', '', '', '' );
    $tab = ob_get_clean();
    die( trim( $tab ) );
  }

  public function display_nav_tabs() {
    $is_enabled = (bool) get_option( 'mepr_account_nav_tabs_enabled', false );
    if ( ! $is_enabled ) {
      return;
    }
    $tabs = self::get_tabs();

    if ( empty( $tabs ) ) {
      return;
    }

    $uri_path = explode( '?', $_SERVER['REQUEST_URI'], 2 );

    foreach ( $tabs as $i => $tab ) {
      $active  = ( isset( $_GET['action'] ) && $_GET['action'] == 'tab' . $i ) ? 'mepr-active-nav-tab' : '';
      $new_tab = ( $tab->new_tab ) ? 'target="_blank"' : '';
      if ( $tab->type === 'content' ) { ?>
        <span class="mepr-nav-item <?php echo $active; ?>">
          <a href="<?php echo $uri_path[0]; ?>?action=tab<?php echo $i; ?>"><?php echo stripslashes( $tab->title ); ?></a>
        </span>
        <?php
      } else if ( $tab->type === 'url' ) { ?>
        <span class="mepr-nav-item">
          <a href="<?php echo stripslashes( $tab->url ); ?>" <?php echo $new_tab; ?>><?php echo stripslashes( $tab->title ); ?></a>
        </span>
        <?php
      }
    }
  }

  public function display_nav_tab_content( $action ) {
    $is_enabled = (bool) get_option( 'mepr_account_nav_tabs_enabled', false );
    if ( ! $is_enabled ) {
      return;
    }
    $tabs = self::get_tabs();

    if ( empty( $tabs ) ) {
      return;
    }

    foreach ( $tabs as $i => $tab ) {
      if ( $action === 'tab' . $i ) {
        ?>
        <div id="mepr_nav_tab_content_<?php echo $i; ?>">
          <?php echo do_shortcode( wpautop( stripslashes( $tab->content ) ) ); ?>
        </div>
        <?php
      }
    }
  }

  public function maybe_redirect_bp_tabs() {
    $is_enabled = (bool) get_option( 'mepr_account_nav_tabs_enabled', false );
    if ( ! $is_enabled ) {
      return;
    }
    $this->display_bp_nav_tab_content( true );
  }

  public function setup_bp_nav() {
    $is_enabled = (bool) get_option( 'mepr_account_nav_tabs_enabled', false );
    if ( ! $is_enabled ) {
      return;
    }
    $append_bp = ( function_exists( 'bp_is_active' ) && get_option( 'mepr_buddypress_enabled', 0 ) ) ? 'mp-subscriptions/' : '';

    if ( empty( $append_bp ) ) {
      return;
    }
    global $bp;

    $tabs = self::get_tabs();
    $slug = MeprHooks::apply_filters( 'mepr-bp-info-main-nav-slug', 'mp-membership' );
    $pos  = 100;

    if ( empty( $tabs ) ) {
      return;
    }
    foreach ( $tabs as $i => $tab ) {
      bp_core_new_subnav_item(
        array(
          'name'            => stripslashes( $tab->title ),
          'slug'            => 'mp-tab-' . $i,
          'parent_url'      => $bp->loggedin_user->domain . $slug . '/',
          'parent_slug'     => $slug,
          'screen_function' => array( $this, 'bp_display_screen' ),
          'position'        => $pos ++,
          'user_has_access' => bp_is_my_profile(),
          'site_admin_only' => false,
          'item_css_id'     => 'mepr-bp-tab-' . $i
        )
      );
    }
  }

  public function bp_display_screen() {
    add_action( 'bp_template_content', array( $this, 'display_bp_nav_tab_content' ) );
    bp_core_load_template( apply_filters( 'bp_core_load_template_plugin', 'members/single/plugins' ) );
  }

  public function display_bp_nav_tab_content( $redirect = false ) {
    $tabs = self::get_tabs();

    if ( empty( $tabs ) ) {
      return;
    }

    foreach ( $tabs as $i => $tab ) {
      if ( strpos($_SERVER['REQUEST_URI'], 'mp-tab-' . $i ) !== false ) {
        if ( ! $redirect ) {
          if ( $tab->type === 'content' ) { ?>
            <div id="mepr_nav_tab_content_<?php echo $i; ?>">
              <?php echo do_shortcode( wpautop( stripslashes( $tab->content ) ) ); ?>
            </div>
          <?php
          } else if ( $tab->type === 'url' ) { ?>
            <div id="mepr_nav_tab_content_<?php echo $i; ?>">
              <p><?php _e( 'Please wait while you are being redirected...', 'memberpress-account-nav-tabs' ); ?></p>
              <meta http-equiv="refresh" content="0; url=<?php echo stripslashes( $tab->url ); ?>" />
            </div>
          <?php
          }
        } else if ( $tab->type === 'url' ) {
          MeprUtils::wp_redirect(stripslashes( $tab->url ) );
          exit;
        }
      }
    }
  }

}
