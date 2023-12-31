<?php

/**
 * This Gateway is slightly special, because as well as providing functions that need to be activated, there are offline payment functions that are always there e.g. adding manual payments.
 * @author marcus
 */
class EM_Gateway_Offline extends EM_Gateway {

	var $gateway = 'offline';
	var $title = 'Offline';
	var $status = 5;
	var $button_enabled = true;
	var $count_pending_spaces = true;
	var $supports_multiple_bookings = true;

	/**
	 * Sets up gateway and registers actions/filters
	 */
	function __construct() {
		parent::__construct();
		add_action('init',array(&$this, 'actions'),10);
		//Booking Interception
		add_filter('em_booking_set_status',array(&$this,'em_booking_set_status'),1,2);
		add_filter('em_bookings_pending_count', array(&$this, 'em_bookings_pending_count'),1,1);
		add_filter('em_bookings_table_booking_actions_5', array(&$this,'bookings_table_actions'),1,2);
		add_filter('em_wp_localize_script', array(&$this,'em_wp_localize_script'),1,1);
		add_action('em_admin_event_booking_options_buttons', array(&$this, 'event_booking_options_buttons'),10);
		add_action('em_admin_event_booking_options', array(&$this, 'event_booking_options'),10);
		add_action('em_bookings_single_metabox_footer', array(&$this, 'add_payment_form'),1,1); //add payment to booking
		add_action('em_bookings_manual_booking', array(&$this, 'add_booking_form'),1,1);
		add_filter('em_booking_get_post', array(&$this,'em_booking_get_post'),1,2);
		add_filter('em_booking_validate', array(&$this,'em_booking_validate'),9,2); //before EM_Bookings_Form hooks in
	}
	
	/**
	 * Run on init, actions that need taking regarding offline bookings are caught here, e.g. registering manual bookings and adding payments 
	 */
	function actions(){
		global $EM_Notices, $EM_Booking, $EM_Event, $wpdb;
		//if manual booking submitted, prevent double bookings check so that current user can book someone else if they are also booked in event
		if( !empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id']) && !empty($_REQUEST['manual_booking_override']) ){
			add_action('pre_option_dbem_bookings_double','__return_true'); //so we don't get a you're already booked here message
			EM_Bookings::$disable_restrictions = true; // disable other restrictions
		}
		//Check if manual payment has been added
		if( !empty($_REQUEST['booking_id']) && !empty($_REQUEST['action']) && !empty($_REQUEST['_wpnonce'])){
			$EM_Booking = em_get_booking($_REQUEST['booking_id']);
			if( $_REQUEST['action'] == 'gateway_add_payment' && is_object($EM_Booking) && wp_verify_nonce($_REQUEST['_wpnonce'], 'gateway_add_payment') ){
				if( !empty($_REQUEST['transaction_total_amount']) && is_numeric($_REQUEST['transaction_total_amount']) ){
					$this->record_transaction($EM_Booking, $_REQUEST['transaction_total_amount'], get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', $_REQUEST['transaction_note']);
					$string = __('Payment has been registered.','em-pro');
					$total = $EM_Booking->get_total_paid();
					if( $total >= $EM_Booking->get_price() ){
						$EM_Booking->approve();
						$string .= " ". __('Booking is now fully paid and confirmed.','em-pro');
					}
					$EM_Notices->add_confirm($string,true);
					do_action('em_payment_processed', $EM_Booking, $this);
					wp_redirect(em_wp_get_referer());
					exit();
				}else{
					$EM_Notices->add_error(__('Please enter a valid payment amount. Numbers only, use negative number to credit a booking.','em-pro'));
					unset($_REQUEST['action']);
					unset($_POST['action']);
				}
			}
		}
	}
	
	function em_wp_localize_script($vars){
		if( is_user_logged_in() && get_option('dbem_rsvp_enabled') ){
			$vars['offline_confirm'] = __('Be aware that by approving a booking awaiting payment, a full payment transaction will be registered against this booking, meaning that it will be considered as paid.','em-pro');
		}
		return $vars;
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */
	
	
	/**
	 * Intercepts return JSON and adjust feedback messages when booking with this gateway.
	 * @param array $return
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	function booking_form_feedback( $return, $EM_Booking = false ){
		if( !empty($return['result']) && !empty($EM_Booking->booking_meta['gateway']) && !empty($EM_Booking->booking_status) ){ //check emtpies
			if( $EM_Booking->booking_status == 5 && $this->uses_gateway($EM_Booking) ){ //check values
				$return['message'] = get_option('em_'.$this->gateway.'_booking_feedback');
				if( !empty($EM_Booking->email_not_sent) ){
					$return['message'] .=  ' '.get_option('dbem_booking_feedback_nomail');
				}
				return apply_filters('em_gateway_offline_booking_add', $return, $EM_Booking->get_event(), $EM_Booking);
			}
		}						
		return $return;
	}
	
	/**
	 * Sets booking status and records a full payment transaction if new status is from pending payment to completed. 
	 * @param int $status
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_set_status($result, $EM_Booking){
		if($EM_Booking->booking_status == 1 && $EM_Booking->previous_status == $this->status && $this->uses_gateway($EM_Booking) && (empty($_REQUEST['action']) || $_REQUEST['action'] != 'gateway_add_payment') ){
			$this->record_transaction($EM_Booking, $EM_Booking->get_price(false,false,true), get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', '');								
		}
		return $result;
	}
	
	function em_bookings_pending_count($count){
		return $count + EM_Bookings::count(array('status'=>'5'));
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing offline bookings
	 * --------------------------------------------------
	 */

	/**
	 * Outputs extra custom information, e.g. payment details or procedure, which is displayed when this gateway is selected when booking (not when using Quick Pay Buttons)
	 */
	function booking_form(){
		echo get_option('em_'.$this->gateway.'_form');
	}
	
	/**
	 * Adds relevant actions to booking shown in the bookings table
	 * @param EM_Booking $EM_Booking
	 */
	function bookings_table_actions( $actions, $EM_Booking ){
		return array(
			'approve' => '<a class="em-bookings-approve em-bookings-approve-offline" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Approve','events-manager').'</a>',
			'reject' => '<a class="em-bookings-reject" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_reject', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Reject','events-manager').'</a>',
			'delete' => '<span class="trash"><a class="em-bookings-delete" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Delete','events-manager').'</a></span>',
			'edit' => '<a class="em-bookings-edit" href="'.em_add_get_params($EM_Booking->get_event()->get_bookings_url(), array('booking_id'=>$EM_Booking->booking_id, 'em_ajax'=>null, 'em_obj'=>null)).'">'.esc_html__emp('Edit/View','events-manager').'</a>',
		);
	}
	
	/**
	 * Adds an add manual booking button to admin pages
	 */
	function event_booking_options_buttons(){
		global $EM_Event;
		?><a href="<?php echo em_add_get_params($EM_Event->get_bookings_url(), array('action'=>'manual_booking','event_id'=>$EM_Event->event_id)); ?>" class="button button-secondary"><?php _e('Add Booking','em-pro') ?></a><?php
	}
	
	/**
	 * Adds a link to add a new manual booking in admin pages
	 */
	function event_booking_options(){
		global $EM_Event;
		?><a href="<?php echo em_add_get_params($EM_Event->get_bookings_url(), array('action'=>'manual_booking','event_id'=>$EM_Event->event_id)); ?>"><?php _e('add booking','em-pro') ?></a><?php	
	}
	
	/**
	 * Adds a payment form which can be used to submit full or partial offline payments for a booking. 
	 */
	function add_payment_form() {
		?>
		<div id="em-gateway-payment" class="stuffbox">
			<h3>
				<?php _e('Add Offline Payment', 'em-pro'); ?>
			</h3>
			<div class="inside">
				<div>
					<form method="post" action="" style="padding:5px;">
						<table class="form-table">
							<tbody>
							  <tr valign="top">
								  <th scope="row"><?php _e('Amount', 'em-pro') ?></th>
									  <td><input type="text" name="transaction_total_amount" value="<?php if(!empty($_REQUEST['transaction_total_amount'])) echo esc_attr($_REQUEST['transaction_total_amount']); ?>" />
									  <br />
									  <em><?php _e('Please enter a valid payment amount (e.g. 10.00). Use negative numbers to credit a booking.','em-pro'); ?></em>
								  </td>
							  </tr>
							  <tr valign="top">
								  <th scope="row"><?php _e('Comments', 'em-pro') ?></th>
								  <td>
										<textarea name="transaction_note"><?php if(!empty($_REQUEST['transaction_note'])) echo esc_attr($_REQUEST['transaction_note']); ?></textarea>
								  </td>
							  </tr>
							</tbody>
						</table>
						<input type="hidden" name="action" value="gateway_add_payment" />
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('gateway_add_payment'); ?>" />
						<input type="hidden" name="redirect_to" value="<?php echo (!empty($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to']:em_wp_get_referer(); ?>" />
						<input type="submit" class="<?php if( is_admin() ) echo 'button-primary'; ?>" value="<?php _e('Add Offline Payment', 'em-pro'); ?>" />
					</form>
				</div>					
			</div>
		</div> 
		<?php
	}

	/* 
	 * --------------------------------------------------
	 * Manual Booking Functions
	 * --------------------------------------------------
	 */
	
	/**
	 * Generates a booking form where an event admin can add a booking for another user. $EM_Event is assumed to be global at this point.
	 */
	function add_booking_form() {
		/* @var $EM_Event EM_Event */   
		global $EM_Notices, $EM_Event;
		if( !is_object($EM_Event) ) { return; }
		//force all user fields to be loaded
		EM_Bookings::$force_registration = EM_Bookings::$disable_restrictions = true;
		//make all tickets available
		foreach( $EM_Event->get_bookings()->get_tickets() as $EM_Ticket ) $EM_Ticket->is_available = true; //make all tickets available
		//remove unecessary footer payment stuff and add our own 
		remove_action('em_booking_form_footer', array('EM_Gateways','booking_form_footer'),10,2);
		remove_action('em_booking_form_footer', array('EM_Gateways','event_booking_form_footer'),10,2);
		add_action('em_booking_form_footer', array($this,'em_booking_form_footer'),10,2);
		add_action('em_booking_form_custom', array($this,'em_booking_form_custom'), 1);
        $header_button_classes = is_admin() ? 'page-title-action':'button add-new-h2';
		add_action('pre_option_dbem_bookings_double','__return_true'); //so we don't get a you're already booked here message
		do_action('em_before_manual_booking_form');
		//Data privacy consent - not added in admin by default, so we add it here
		if( get_option('dbem_data_privacy_consent_bookings') > 0 ){
			add_filter('pre_option_dbem_data_privacy_consent_remember', '__return_zero');
			add_action('em_booking_form_footer', 'em_data_privacy_consent_checkbox', 9, 0); //supply 0 args since arg is $EM_Event and callback will think it's an event submission form
		}
		?>
		<div class='wrap em-manual-booking'>
            <?php if( is_admin() ): ?>
				<h1 class="wp-heading-inline"><?php echo sprintf(__('Add Booking For &quot;%s&quot;','em-pro'), $EM_Event->name); ?></h1>
				<a href="<?php echo esc_url($EM_Event->get_bookings_url()); ?>" class="<?php echo $header_button_classes; ?>"><?php echo esc_html(sprintf(__('Go back to &quot;%s&quot; bookings','em-pro'), $EM_Event->name)) ?></a>
                <hr class="wp-header-end" />
			<?php else: ?>
				<h2>
					<?php echo sprintf(__('Add Booking For &quot;%s&quot;','em-pro'), $EM_Event->name); ?>
					<a href="<?php echo esc_url($EM_Event->get_bookings_url()); ?>" class="<?php echo $header_button_classes; ?>"><?php echo esc_html(sprintf(__('Go back to &quot;%s&quot; bookings','em-pro'), $EM_Event->name)) ?></a>
                </h2>
            <?php endif; ?>
			<?php echo $EM_Event->output('#_BOOKINGFORM'); ?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$('.em-tickets').addClass('widefat');
					var user_fields = $('.em-booking-form p.input-user-field');
					$('select#person_id').on('change', function(e){
						var person_id = $('select#person_id option:selected').val();
						person_id > 0 ? user_fields.hide() : user_fields.show();
						<?php if( get_option('dbem_data_privacy_consent_bookings') > 0 ): remove_filter('pre_option_dbem_data_privacy_consent_remember', '__return_zero'); ?>
							var consent_enabled = <?php echo esc_js( get_option('dbem_data_privacy_consent_bookings') ); ?>;
							var consent_remember = <?php echo esc_js( get_option('dbem_data_privacy_consent_remember') ); ?>;
							var consent_field = $('.em-booking-form p.input-field-data_privacy_consent');
							var consent_checkbox = consent_field.find('input[type="checkbox"]').prop('checked', false);
							if( person_id > 0 ){
								$('.em-booking-form p.input-user-field').hide();
								if( consent_enabled === 1 ){
									var consented = Number($(this).find(':selected').data('consented')) === 1;
									if( consent_remember > 0 ){
										consent_checkbox.prop('checked', consented);
										if( consent_remember === 1 ) consented ? consent_field.hide() : consent_field.show();
									}
								}else if( consent_enabled === 2 ){
									consent_field.hide();
								}
							}else{
								$('.em-booking-form p.input-user-field').show();
								consent_field.show();
							}
						<?php endif; ?>
					});
				});
			</script>
		</div>
		<?php
		do_action('em_after_manual_booking_form');
		//add js that calculates final price, and also user auto-completer
		//if user is chosen, we use normal registration and change person_id after the fact
		//make sure payment amounts are resporcted
	}
	
	/**
	 * Modifies the booking status if the event isn't free and also adds a filter to modify user feedback returned.
	 * Triggered by the em_booking_add_yourgateway action.
	 * @param EM_Event $EM_Event
	 * @param EM_Booking $EM_Booking
	 * @param boolean $post_validation
	 */
	function booking_add($EM_Event,$EM_Booking, $post_validation = false){
		global $wpdb, $wp_rewrite, $EM_Notices;
		//manual bookings
		if( !empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id']) ){
			//validate post
			if( !empty($_REQUEST['payment_amount']) && !is_numeric($_REQUEST['payment_amount'])){
				$EM_Booking->add_error( 'Invalid payment amount, please provide a number only.', 'em-pro' );
			}
			//add em_event_save filter to log transactions etc.
			add_filter('em_booking_save', array(&$this, 'em_booking_save'), 10, 2);
			//set flag that we're manually booking here, and set gateway to offline
			if( empty($_REQUEST['person_id']) || $_REQUEST['person_id'] < 0 ){
				EM_Bookings::$force_registration = true;
			}
		}
		parent::booking_add($EM_Event, $EM_Booking, $post_validation);
	}
	
	/**
	 * Hooks into the em_booking_save filter and checks whether a partial or full payment has been submitted
	 * @param boolean $result
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_save( $result, $EM_Booking ){
		if( $result && !empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id']) ){
			remove_filter('em_booking_set_status', array(&$this, 'em_booking_set_status'), 1, 2);
			if( $this->is_active() ) {
				if (!empty($_REQUEST['payment_full'])) {
					$price = (!empty($_REQUEST['payment_amount']) && is_numeric($_REQUEST['payment_amount'])) ? $_REQUEST['payment_amount'] : $EM_Booking->get_price(false, false, true);
					$this->record_transaction($EM_Booking, $price, get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', __('Manual booking.', 'em-pro'));
					$EM_Booking->set_status(1, false);
				} elseif (!empty($_REQUEST['payment_amount']) && is_numeric($_REQUEST['payment_amount'])) {
					$this->record_transaction($EM_Booking, $_REQUEST['payment_amount'], get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', __('Manual booking.', 'em-pro'));
					if ($_REQUEST['payment_amount'] >= $EM_Booking->get_price(false, false, true)) {
						$EM_Booking->set_status(1, false);
					}
				}
			} else {
				// we still currently process manual bookings via offline gateway (to be changed), so we restore the booking status to what it should be without gateways
				$booking_status = !get_option('dbem_bookings_approval') || !empty($_REQUEST['manual_booking_confirm']) ? 1:0;
				$EM_Booking->set_status($booking_status, false);
			}
			add_filter('em_booking_set_status',array(&$this,'em_booking_set_status'),1,2);
			add_filter('em_action_booking_add', 'EM_Gateway_Offline::em_action_booking_add');
		}
		return $result;
	}
	
	public static function em_action_booking_add( $feedback ){
		$add_txt = '<a href="'.em_wp_get_referer().'"">'.__('Add another booking','em-pro').'</a>';
		$feedback["message"] = esc_html__emp('Booking Successful') .'<br><br>'. $add_txt;
		return $feedback;
	}
	
	function em_booking_validate($result, $EM_Booking){
		if( !empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id']) ){
			if( !empty($_REQUEST['person_id']) ){
				//@todo allow users to update user info during manual booking
				add_filter('option_dbem_emp_booking_form_reg_input', '__return_false');
				//impose double bookings here, because earlier we had to disable it due to the fact that the logged in admin is checked for double booking rather than represented user
		  		remove_all_actions('pre_option_dbem_bookings_double'); //so we don't get a you're already booked here message
				if( !get_option('dbem_bookings_double') && $EM_Booking->get_event()->get_bookings()->has_booking($_REQUEST['person_id']) ){
					$result = false;
					$EM_Booking->add_error( get_option('dbem_booking_feedback_already_booked') );
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param boolean $result
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_get_post( $result, $EM_Booking ){
		if( $result && !empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id']) ){
			if( !empty($_REQUEST['person_id']) ){
				$person = new EM_Person($_REQUEST['person_id']);
				if( !empty($person->ID) ){
					$EM_Booking->person = $person;
					$EM_Booking->person_id = $person->ID;
				}
			}elseif( get_option('dbem_bookings_registration_disable') ){
				//for no-user bookings mode we circumvent
				$EM_Booking->person = new EM_Person(0);
				$EM_Booking->person_id = 0;
			}
		}
		return $result;
	}
	
	/**
	 * Called before EM_Forms fields are added, when a manual booking is being made
	 */
	function em_booking_form_custom(){
		global $wpdb;
		?>
		<p>
			<?php
				$person_id = (!empty($_REQUEST['person_id'])) ? $_REQUEST['person_id'] : false;
				//get consent info for each user, for use later on
				$user_consents_raw = $wpdb->get_results("SELECT user_id, meta_value FROM " . $wpdb->usermeta . " WHERE meta_key='em_data_privacy_consent' GROUP BY user_id");
				$user_consents = array();
				foreach( $user_consents_raw as $user_consent ) $user_consents[$user_consent->user_id] = $user_consent->meta_value;
				//output list of users
				$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array('ID','display_name','user_login') ) );
				if( !empty( $users ) ){
					$placeholder = esc_html__( "Select a user (type to search), or enter a new one below.", 'em-pro' );
					$selectized = apply_filters('em_gateway_offline_select_user_manual_booking', 'em-selectize', $this);
					echo '<select name="person_id" id="person_id" class="'. $selectized .'" placeholder="'.$placeholder.'">';
					$_selected = selected( 0, $person_id, false );
					echo "\t<option value=''>" . $placeholder . "</option>\n";
					foreach ( (array) $users as $user ) {
						$display = sprintf( _x( '%1$s (%2$s)', 'user dropdown' ), $user->display_name, $user->user_login );
						$_selected = selected( $user->ID, $person_id, false );
						$consented = !empty($user_consents[$user->ID]) ? 1:0;
						echo "\t<option value='$user->ID' data-consented='$consented'$_selected>" . esc_html( $display ) . "</option>\n";
					}
					echo '</select>';
				}
				//wp_dropdown_users ( array ('name' => 'person_id', 'show_option_none' => __ ( "Select a user, or enter a new one below.", 'em-pro' ), 'selected' => $person_id  ) );
			?>
		</p>
		<?php
	}
	
	/**
	 * Called instead of the filter in EM_Gateways if a manual booking is being made
	 * @param EM_Event $EM_Event
	 */
	function em_booking_form_footer($EM_Event){
		if( $EM_Event->can_manage('manage_bookings','manage_others_bookings') ){
			//Admin is adding a booking here, so let's show a different form here.
			?>
			<input type="hidden" name="manual_booking" value="<?php echo wp_create_nonce('em_manual_booking_'.$EM_Event->event_id); ?>" />
			<input type="hidden" name="gateway" value="<?php echo $this->gateway; ?>" />
			<?php
			if( $this->is_active() ){
				?>
				<p class="em-booking-gateway" id="em-booking-gateway">
					<label><?php _e('Amount Paid','em-pro'); ?></label>
					<input type="text" name="payment_amount" id="em-payment-amount" value="<?php if(!empty($_REQUEST['payment_amount'])) echo esc_attr($_REQUEST['payment_amount']); ?>">
					<?php _e('Fully Paid','em-pro'); ?> <input type="checkbox" name="payment_full" id="em-payment-full" value="1"><br />
					<em><?php _e('If you check this as fully paid, and leave the amount paid blank, it will be assumed the full payment has been made.' ,'em-pro'); ?></em>
				</p>
				<?php
			}elseif( get_option('dbem_bookings_approval') ){
				?>
				<p class="input-group input-checkbox input-manual-payment-status">
					<label style="width:100%;">
						<input type="checkbox" name="manual_booking_confirm" value="1" checked>
						<?php _e('Confirm Booking?','em-pro'); ?>
					</label>
					<br style="clear:both;">
					<em><?php _e('If you check this, the booking will be marked as confirmed automatically.' ,'em-pro'); ?></em>
				</p>
				<?php
			}
			?>
			<p class="input-group input-checkbox">
				<label style="width:100%;">
					<input type="checkbox" name="manual_booking_override" value="1">
					<?php _e('Override any restrictions to ticket availability and limits (this may lead to overbooking).','em-pro'); ?>
				</label>
				<br style="clear:both;">
			</p>
			<?php
		}
		return;
	}
	
	/* 
	 * --------------------------------------------------
	 * Settings pages and functions
	 * --------------------------------------------------
	 */
	
	/**
	 * Outputs custom offline setting fields in the settings page 
	 */
	function mysettings() {

		global $EM_options;
		?>
		<table class="form-table">
		<tbody>
		  <?php em_options_input_text( esc_html__('Success Message', 'em-pro'), 'em_'. $this->gateway . '_booking_feedback', esc_html__('The message that is shown to a user when a booking with offline payments is successful.','em-pro') )?>
		</tbody>
		</table>
		<?php
	}

	/* 
	 * Run when saving PayPal settings, saves the settings available in EM_Gateway_Paypal::mysettings()
	 */
	function update() {
	    $gateway_options = array('em_'. $this->gateway . '_booking_feedback');
		foreach( $gateway_options as $option_wpkses ) add_filter('gateway_update_'.$option_wpkses,'wp_kses_post');
		return parent::update($gateway_options);
	}	
	
	/**
	 * Checks an EM_Booking object and returns whether or not this gateway is/was used in the booking.
	 * @param EM_Booking $EM_Booking
	 * @return boolean
	 */
	function uses_gateway($EM_Booking){
	    //for all intents and purposes, if there's no gateway assigned but this booking status matches, we assume it's offline
		return parent::uses_gateway($EM_Booking) || ( empty($EM_Booking->booking_meta['gateway']) && $EM_Booking->booking_status == $this->status );
	}
}
EM_Gateways::register_gateway('offline', 'EM_Gateway_Offline');
?>