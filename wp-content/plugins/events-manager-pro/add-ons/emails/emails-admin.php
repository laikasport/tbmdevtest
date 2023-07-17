<?php
class EM_Emails_Admin {
    
    public static function init(){
        add_action('em_options_page_footer_emails', 'EM_Emails_Admin::reminder_options');
        add_action('em_options_page_footer_emails', 'EM_Emails_Admin::custom_email_options');
	    add_action('em_options_page_footer_emails', 'EM_Emails_Admin::email_bookings_options');
	    add_action('em_options_page_booking_email_templates_options_subtop', 'EM_Emails_Admin::ical_attachment_bookings');
	    add_action('em_options_page_multiple_booking_email_templates_options_subtop', 'EM_Emails_Admin::ical_attachment_multiple_bookings');
    }
    
    public static function ical_attachment_bookings(){
	    em_options_radio_binary ( esc_html__( 'Add iCal invite?', 'em-pro'), 'dbem_bookings_ical_attachments', esc_html__( 'You can choose to add ical attachments to your booking emails, which display event information in email clients such as gmail and outlook allowing for easy adding to calendars.', 'em-pro') );
    }
	
	public static function ical_attachment_multiple_bookings(){
    	$extra_message = esc_html__('Note that for bookings with multiple events, some clients such as Gmail will only show the first event (this is a limitation in their parsing of ical), however the ical file can be downloaded and added to calendar clients for all the events.', 'em-pro');
		em_options_radio_binary ( esc_html__( 'Add iCal invite?', 'em-pro'), 'dbem_multiple_bookings_ical_attachments', __( 'You can choose to add ical attachments to your booking emails, which display event information in email clients such as gmail and outlook allowing for easy adding to calendars.', 'em-pro') .' '. $extra_message );
	}
	
    /*
     * --------------------------------------------
     * Email Reminders
     * --------------------------------------------
     */
	/**
	 * Generates meta box for settings page 
	 */
	public static function reminder_options(){
	    global $save_button;
	    ?>
		<div  class="postbox " id="em-opt-email-reminders" >
		<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle', 'events-manager'); ?>"><br /></div><h3><?php _e ( 'Event Email Reminders', 'em-pro' ); ?></h3>
		<div class="inside">
			<table class='form-table'>
				<tr class="em-boxheader"><td colspan='2'>
					<p>
						<?php _e( 'Events Manager can send people that booked a place at your events a reminder email before it starts.', 'em-pro' );  ?>
						<?php echo sprintf(__('We use <a href="%s">WP Cron</a> for scheduling checks for future events, which relies on site visits to trigger these tasks to run. If you have low levels of site traffic, this may not happen frequently enough, so you may want to consider forcing WP-Cron to run every few minutes. For more information, <a href="%s">read this tutorial</a> on setting up WP Cron.','em-pro'),'http://codex.wordpress.org/Category:WP-Cron_Functions','http://code.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress--wp-23119'); ?>
					</p>
					<p><?php _e('<strong>Important!</strong>, you should use SMTP as your email setup if you are sending automated emails in this way for optimal performance. Other methods are not suited to sending mass emails.', 'em-pro'); ?>
				</td></tr>
				<?php
				em_options_radio_binary ( sprintf(_x( 'Enable %s?', 'Enable a feature in settings page', 'em-pro' ), __('Event Email Reminders','em-pro')), 'dbem_cron_emails','');
				em_options_input_text ( __( 'Days before reminder', 'em-pro' ), 'dbem_emp_emails_reminder_days',__('You can choose to send people attending your event x days before the event starts. Minimum is one day.', 'em-pro'), 1);
				em_options_radio_binary ( __( 'Attach ical invite?', 'em-pro' ), 'dbem_emp_emails_reminder_ical',__('If using SMTP in your email settings. You can automatically attach an ical file which some email clients (e.g. gmail) will render as an invitation they can add to their calendar.', 'em-pro'));
				$days = get_option('dbem_emp_emails_reminder_days',1);
				?>
				<tr>
					<th><?php _e('WP Cron Time','em-pro'); ?></th>
					<td>
						<input class="em-time-input em-time-start" type="text" name="dbem_emp_emails_reminder_time" value="<?php echo get_option('dbem_emp_emails_reminder_time','12:00 AM'); ?>" /><br />
						<em><?php _e('Every day Events Manager automatically checks upcoming events in order to generate emails. You can choose at what time of day to run this check, if your site has a lot of traffic, it may help having this run at times of lower server loads.','em-pro'); ?></em>
					</td>
				</tr>
				<?php
				em_options_input_text ( __( 'Reminder subject', 'em-pro' ), 'dbem_emp_emails_reminder_subject','');
				em_options_textarea ( __( 'Approved email', 'em-pro' ), 'dbem_emp_emails_reminder_body','');
				?>
				<?php echo $save_button; ?>
			</table>
		</div> <!-- . inside -->
		</div> <!-- .postbox -->
	    <?php
	}
	
    /*
     * --------------------------------------------
     * Custom Event/Gateway Booking Emails
     * --------------------------------------------
     */
	
	/**
	 * Generates meta box for settings page 
	 */
	public static function custom_email_options(){
	    global $save_button;
	    ?>
		<div  class="postbox " id="em-opt-custom-emails" >
		<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle', 'events-manager'); ?>"><br /></div><h3><?php _e ( 'Custom Booking Email Templates', 'em-pro' ); ?></h3>
		<div class="inside">
			<table class='form-table'>
				<tr class="em-boxheader"><td colspan='2'>
					<p><?php _e( 'You can customize the email templates sent when users make a booking for one of your events.', 'em-pro' );  ?></p>
				</td></tr>
				<?php
				em_options_radio_binary ( sprintf(_x( 'Enable %s?', 'Enable a feature in settings page', 'em-pro' ), __('Custom Booking Email Templates','em-pro')), 'dbem_custom_emails','');
				?>
				<tbody class="dbem-js-custom-emails">
					<tr class="em-header"><td colspan="2"><h4><?php _e('Event Emails','em-pro'); ?></h4></td></tr>
					<?php
					em_options_radio_binary ( __( 'Allow custom emails for events?', 'em-pro' ), 'dbem_custom_emails_events',__('Allow custom booking email templates to be configured for individual events.','em-pro').' '.__('Users with the %s user capability will be able to do this when adding/editing events.','em-pro'));
					em_options_radio_binary ( __( 'Allow custom admin email addresses for events?', 'em-pro' ), 'dbem_custom_emails_events_admins',__('Allow adding custom email addresses to be addded to individual events.','em-pro').' '.__('Users with the %s user capability will be able to do this when adding/editing events.','em-pro'));
					?>
					<tr class="em-header"><td colspan="2"><h4><?php _e('Gateway Emails','em-pro'); ?></h4></td></tr>
					<?php
					em_options_radio_binary ( __( 'Allow custom emails for gateways?', 'em-pro' ), 'dbem_custom_emails_gateways', sprintf(__('Allow administrators of this blog to configure custom booking email templates inside each %s settings page.','em-pro'),'<a href="'.admin_url('edit.php?post_type=event&page=events-manager-gateways').'">'.__('Payment Gateways','em-pro').'</a>') );
					em_options_radio_binary ( __( 'Allow custom admin email addresses for gateways?', 'em-pro' ), 'dbem_custom_emails_gateways_admins', sprintf(__('Allow administrators of this blog to add additional admin email addresses for gateways inside each %s settings page.','em-pro'),'<a href="'.admin_url('edit.php?post_type=event&page=events-manager-gateways').'">'.__('Payment Gateways','em-pro').'</a>') );
					?>
				</tbody>
				<?php echo $save_button; ?>
			</table>
		</div> <!-- . inside -->
		</div> <!-- .postbox -->
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('input:radio[name="dbem_custom_emails"]').on('change', function(){
					if( $('input:radio[name="dbem_custom_emails"]:checked').val() == 1 ){
						$('tbody.dbem-js-custom-emails').show();
					}else{
						$('tbody.dbem-js-custom-emails').hide();					
					}
				}).first().trigger('change');
				$('input:radio[name="dbem_custom_emails_events"], input:radio[name="dbem_custom_emails_gateways"]').on('change', function(){
					if( $('input:radio[name="'+this.name+'"]:checked').val() == 1 ){
						$('tr#'+this.name+'_admins_row').show();
					}else{
						$('tr#'+this.name+'_admins_row').hide();
					}
				}).filter('input:radio:checked').trigger('change');
			});
		</script>
	    <?php
	}
	
	public static function email_bookings_options(){
		global $save_button, $bookings_placeholder_tip;
		?>
		<div  class="postbox " id="em-opt-email-bookings" >
			<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle'); ?>"><br /></div><h3><?php esc_html_e( 'Send Booked User Emails', 'em-pro' ); ?></h3>
			<div class="inside">
				<table class='form-table'>
					<tr class="em-boxheader">
						<td colspan='2'>
							<p><?php _e( 'By enabling this feature, event admins who can manage bookings can also send a mass email to users who have a booking at a specific event, which can make use of placeholders to provide unique information to each booking email. This feature will be available as a link/button when viewing the event bookings admin or the bookings admin section whilst editing an event.', 'em-pro' );  ?></p>
						</td>
					</tr>
					<?php
					em_options_radio_binary ( sprintf(_x( 'Enable %s?', 'Enable a feature in settings page', 'em-pro' ), __('Emailing Booked Users','em-pro')), 'dbem_email_bookings','', '', '.dbem-email-bookings');
					?>
					<tbody class="dbem-email-bookings">
					<tr class="em-header">
						<td colspan="2">
							<h4><?php esc_html_e('Default Email Template','em-pro'); ?></h4>
							<p><?php esc_html_e('The following email templates will be used as the initial email content for sending to booked users.', 'em-pro'); echo ' '. $bookings_placeholder_tip; ?></p>
						</td>
					</tr>
					<?php
					em_options_input_text ( esc_html__( 'Subject', 'em-pro'), 'dbem_email_bookings_default_subject' );
					em_options_textarea ( esc_html__( 'Message', 'em-pro'), 'dbem_email_bookings_default_body' );
					?>
					</tbody>
					<?php echo $save_button; ?>
				</table>
			</div> <!-- . inside -->
		</div> <!-- .postbox -->
		<?php
	}
}
EM_Emails_Admin::init();