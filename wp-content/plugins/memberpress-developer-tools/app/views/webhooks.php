<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<?php $admin = MpdtCtrlFactory::fetch('admin'); ?>
<div class="mepr-page-title"><?php _e('Webhooks', 'memberpress-developer-tools'); ?></div>
  <h3><?php _e('Webhook Key:', 'memberpress-developer-tools'); ?></h3>
  <em>
    <?php _e('The Webhook Key can be used for authenticating the POST request. If you feel your key has been compromised, you can regenerate a new one. To validate Webhook request, fetch HTTP headers and look for <b>MEMBERPRESS-WEBHOOK-KEY</b>.', 'memberpress-developer-tools'); ?>
  </em>
  <p>
    <input id="mpdt_webhook_key" type="text" name="mpdt_webhook_key" value="<?php echo $webhook_key; ?>" onfocus="this.select();" onclick="this.select();" readonly>
    <span>
      <i class="mpdt-clipboard mp-icon mp-icon-clipboard mp-16" data-clipboard-target="#mpdt_webhook_key"></i>
      <i class="mpdt-regenerate-webhook mp-icon mp-icon-arrows-cw mp-16"></i>
    </span>
  </p>
  <hr/>
<p><?php _e('Webhooks can send JSON push notices to specific URLs via POST when specific events occur in MemberPress. You can configure your webhooks here:', 'memberpress-developer-tools'); ?></p>

<form action="" method="post" id="mpdt_ops_form">
  <?php
    if($webhooks !== false && !empty($webhooks)) {
      foreach($webhooks as $count => $webhook) {
        $admin->webhook_row($count, $webhook);
      }
    }
    else {
      $admin->webhook_row(0);
    }
  ?>

  <div>
    <a href="" class="mpdt_add_row" title="<?php _e('Add Webhook URL', 'memberpress-developer-tools'); ?>"><i class="mp-icon mp-icon-plus-circled mp-24"></i></a>
  </div>

  <div class="mpdt_spacer"></div>

  <input type="submit" class="button button-primary" value="<?php _e('Save Webhooks', 'memberpress-developer-tools'); ?>" />
</form>

