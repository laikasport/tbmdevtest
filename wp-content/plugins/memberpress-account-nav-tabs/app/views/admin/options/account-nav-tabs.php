<?php if ( ! defined( 'ABSPATH' ) ) {
  die( 'You are not allowed to call this page directly.' );
} ?>
<h3><?php esc_html_e( 'Account Nav Tabs Settings', 'memberpress-account-nav-tabs' ); ?></h3>
<div class="mepr-options-pane">
  <div id="mepr_account_nav_tabs">
    <label for="mepr_account_nav_tabs_enabled">
      <input type="checkbox" name="mepr_account_nav_tabs_enabled"
             id="mepr_account_nav_tabs_enabled" <?php checked( $is_enabled ); ?> />
      <span><?php _e( 'Enable Account Nav Tabs', 'memberpress-account-nav-tabs' ); ?></span>
    </label>
  </div>
  <br/>
  <div id="mepr_account_nav_tabs_items">
    <div id="mepr_account_nav_tabs_list"><?php MpAccountNavTabsHelper::render_tabs(); ?></div>
    <?php if ( $no_tabs ) { ?>
      <p><?php _e( 'Click the (+) below to add a new tab to MemberPress Account page.', 'memberpress-account-nav-tabs' ); ?></p>
    <?php } ?>
    <a href="" id="mepr-add-tab" title="<?php _e( 'Add a New Tab', 'memberpress-account-nav-tabs' ); ?>"><i
          class="mp-icon mp-icon-plus-circled mp-24"></i> <?php _e( 'Add Nav Tab', 'memberpress-account-nav-tabs' ); ?>
    </a>
  </div>
</div>
