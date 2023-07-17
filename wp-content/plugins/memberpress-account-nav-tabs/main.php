<?php
/*
Plugin Name: MemberPress Account Nav Tabs
Plugin URI: http://memberpress.com
Description: Add new tabs to MemberPress Account page.
Version: 1.0.0
Author: Caseproof, LLC
Author URI: http://caseproof.com
Text Domain: memberpress-account-nav-tabs
Copyright: 2004-2021, Caseproof, LLC
*/

if ( ! defined( 'ABSPATH' ) ) {
  die( 'You are not allowed to call this page directly.' );}

// Let's run the addon
add_action( 'plugins_loaded', function() {

  if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }

  // Bail if MemberPress is not active
  if ( ! is_plugin_active( 'memberpress/memberpress.php' ) && ! defined( 'MEPR_PLUGIN_NAME' ) ) {
    return;
  }

  // Define useful stuffs
  define( 'MACCONTNAVTABS_SLUG', 'memberpress-account-nav-tabs' );
  define( 'MACCONTNAVTABS_FILE', MACCONTNAVTABS_SLUG . '/main.php' );
  define( 'MACCONTNAVTABS_PATH', plugin_dir_path( __FILE__ ) );
  define( 'MACCONTNAVTABS_APP', MACCONTNAVTABS_PATH . 'app/' );
  define( 'MACCONTNAVTABS_URL', plugin_dir_url( __FILE__ ) );

  // if __autoload is active, put it on the spl_autoload stack
  if ( is_array( spl_autoload_functions() ) && in_array( '__autoload', spl_autoload_functions() ) ) {
    spl_autoload_register( '__autoload' );
  }
  spl_autoload_register( 'maccountnavtabs_autoloader' );

  // Load Update Mechanism
  new MeprAddonUpdates(
    MACCONTNAVTABS_SLUG,
    MACCONTNAVTABS_FILE,
    'mepraccountnavtabs_license_key',
    esc_html__( 'MemberPress Account Nav Tabs', 'memberpress-account-nav-tabs' ),
    esc_html__( 'Account Nav Tabs Integration for MemberPress', 'memberpress-account-nav-tabs' )
  );

  // Run Addon
  new MpAccountNavTabsCtrl();
}, 10 );

/**
 * Autoload all the requisite classes
 *
 * @param  string $class_name
 *
 * @return mixed
 */
function maccountnavtabs_autoloader( $class_name ) {
  // Only load MemberPress classes here
  if ( preg_match( '/^MpAccountNavTabs.+$/', $class_name ) ) {
    $filepath = '';
    $filename = maccountnavtabs_filename( $class_name );
    if ( preg_match( '/^.+Ctrl$/', $class_name ) ) {
      $filepath = MACCONTNAVTABS_APP . 'controllers/' . $filename;
    } elseif ( preg_match( '/^.+Helper$/', $class_name ) ) {
      $filepath = MACCONTNAVTABS_APP . 'helpers/' . $filename;
    }

    if ( file_exists( $filepath ) ) {
      require_once $filepath;
    }
  } elseif ( 'MeprAddonUpdates' === $class_name ) {
    require_once MEPR_PATH . '/app/lib/MeprAddonUpdates.php';
  }
}

/**
 * Converts class name to lower case
 *
 * @param  string $class_name
 *
 * @return string
 */
function maccountnavtabs_filename( $class_name ) {
  return $class_name . '.php';
}
