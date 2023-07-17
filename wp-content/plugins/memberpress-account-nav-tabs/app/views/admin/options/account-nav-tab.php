<?php if ( ! defined( 'ABSPATH' ) ) {
  die( 'You are not allowed to call this page directly.' );
} ?>
<div id="mepr_nav_tab_item_<?php echo $id;?>" class="mepr_nav_tab_item" data-id="<?php echo $id; ?>">
  <div class="mepr_nav_tab_item_delete">
    <a href=""><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a>
  </div>
  <div class="mp-row">
    <div class="mepr_nav_tab_form">
      <div class="mp-row">
        <label for="mepr_nav_tab_title_<?php echo $id; ?>"><?php _e( 'Title:', 'memberpress-account-nav-tabs' ); ?></label>
        <input type="text" id="<?php echo "mepr_nav_tab_title_$id"; ?>" class="mepr_nav_tab_title" name="mepr_account_nav_tab[<?php echo $id; ?>][title]" value="<?php echo isset( $title ) ? stripslashes( $title ) : ''; ?>"/><br/>
      </div>
      <br/>
      <div class="mp-row">
        <label for="mepr_account_nav_tabs_tab_label"><?php _e( 'Tab Type:', 'memberpress-account-nav-tabs' ); ?></label>
        <label for="mepr_account_nav_tabs_tab[<?php echo $id; ?>][type]" class="mepr_account_nav_tabs_tab_content">
          <input type="radio" name="mepr_account_nav_tab[<?php echo $id; ?>][type]" id="mepr_account_nav_tabs_tab[<?php echo $id; ?>-content" class="mepr_account_nav_tabs_tab_radio" value="content" data-id="<?php echo $id; ?>" data-type="content" <?php checked( $type == 'content' ); ?> />
          <span><?php _e( 'Content', 'memberpress-account-nav-tabs' ); ?></span>
        </label>
        <label for="mepr_account_nav_tabs_tab[<?php echo $id; ?>][type]">
          <input type="radio" name="mepr_account_nav_tab[<?php echo $id; ?>][type]" id="mepr_account_nav_tabs_tab[<?php echo $id; ?>-url" class="mepr_account_nav_tabs_tab_radio" value="url" data-id="<?php echo $id; ?>" data-type="url" <?php checked( $type == 'url' ); ?> />
          <span><?php _e( 'URL', 'memberpress-account-nav-tabs' ); ?></span>
        </label><br/>
        <div id="mepr_nav_tab_content_<?php echo $id; ?>" class="mepr_nav_tab_content_wrapper <?php echo $content_hidden; ?>">
          <?php if ( wp_doing_ajax() ) { ?>
          <textarea id="<?php echo $id; ?>" class="mepr_nav_tab_textarea" autocomplete="off" name="mepr_account_nav_tab[<?php echo $id; ?>][content]"><?php _e('Enter your content here. After saving, this field will become a full WYSIWYG editor.', 'memberpress-account-nav-tabs' ); ?></textarea>
          <?php } else {
            wp_editor( stripslashes( $content ), 'navtabcontent' . $id, $editor_settings );
          } ?>
          <p class="description"><?php _e('This content will appear below the navigation on the Account Page when you click the tab with the title above.', 'memberpress-account-nav-tabs'); ?></p>
        </div>
        <br/>
        <div id="mepr_nav_tab_url_<?php echo $id; ?>" class="mepr_nav_tab_url_wrapper <?php echo $url_hidden; ?>">
          <label for="mepr_nav_tab_url_<?php echo $id; ?>"><?php _e( 'Enter URL:', 'memberpress-account-nav-tabs' ); ?></label>
          <input type="text" id="<?php echo "mepr_nav_tab_url_<?php echo $id; ?>"; ?>" name="mepr_account_nav_tab[<?php echo $id; ?>][url]" value="<?php echo stripslashes( $url ); ?>"/><br/><br/>
          <label for="<?php echo "mepr_nav_tab_new_tab_$id"; ?>" class="<?php echo $bp_is_active; ?>">
            <input type="checkbox" name="mepr_account_nav_tab[<?php echo $id; ?>][new_tab]" id="<?php echo "mepr_nav_tab_new_tab_$id"; ?>" <?php checked( $new_tab ); ?> />
            <span><?php _e( 'Open URL in the new tab', 'memberpress-account-nav-tabs' ); ?></span>
          </label>
        </div>
      </div>
    </div>
  </div>
</div>
