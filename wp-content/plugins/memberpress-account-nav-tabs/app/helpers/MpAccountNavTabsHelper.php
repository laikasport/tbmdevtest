<?php
if ( ! defined( 'ABSPATH' ) ) {
  die( 'You are not allowed to call this page directly.' );
}

class MpAccountNavTabsHelper {

  public static function render_tabs() {
    $count = 1;
    $tabs = MpAccountNavTabsCtrl::get_tabs();

    if ( $tabs && is_array( $tabs ) ) {
      foreach ( $tabs as $tab ) {
        self::render_tab( $count++, $tab->title, $tab->type, $tab->url, $tab->content, $tab->new_tab );
      }
    } else {
      self::render_tab( $count, __( 'Tab Title', 'memberpress-account-nav-tabs' ), 'content', '', '', '' );
    }
  }

  public static function render_tab( $id, $title, $type, $url, $content, $new_tab ) {
    $url_hidden      = ( $type == 'content' ) ? 'is-hidden' : '';
    $bp_is_active    = ( function_exists( 'bp_is_active' ) && get_option( 'mepr_buddypress_enabled', 0 ) ) ? 'mepr_new_tab_hidden' : '';
    $content_hidden  = ( $type == 'url' ) ? 'is-hidden' : '';
    $editor_settings = array(
      'textarea_name' => 'mepr_account_nav_tab[' . $id . '][content]',
      'teeny'         => true,
      'editor_height' => 200,
    );
    MeprView::render( '/admin/options/account-nav-tab', get_defined_vars() );
  }

}
