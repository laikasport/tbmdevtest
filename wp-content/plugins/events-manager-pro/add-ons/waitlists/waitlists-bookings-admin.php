<?php
namespace EM\Waitlist;

class Bookings_Admin {
	
	public static function init(){
		add_action('em_bookings_table', '\EM\Waitlist\Bookings_Admin::em_bookings_table', 10, 1);
		add_action('em_admin_event_booking_availibility', '\EM\Waitlist\Bookings_Admin::em_admin_event_booking_availibility', 10, 1);
		add_filter('em_bookings_table_booking_actions_6', '\EM\Waitlist\Bookings_Admin::bookings_table_actions', 10, 2);
		add_filter('em_bookings_table_booking_actions_7', '\EM\Waitlist\Bookings_Admin::bookings_table_actions', 10, 2);
		add_filter('em_bookings_table_booking_actions_8', '\EM\Waitlist\Bookings_Admin::bookings_table_actions', 10, 2);
		add_filter('em_bookings_table_booking_actions_3', '\EM\Waitlist\Bookings_Admin::bookings_table_actions', 10, 2);
		
		add_action('em_admin_event_booking_before_availibility', '\EM\Waitlist\Bookings_Admin::em_admin_event_booking_before_availibility', 10, 2);
		add_action('em_admin_event_booking_after_availibility', '\EM\Waitlist\Bookings_Admin::em_admin_event_booking_after_availibility', 10, 2);
		
		
		add_action('em_bookings_table_cols_template', '\EM\Waitlist\Bookings_Admin::em_bookings_table_cols_template',10,1);
		add_filter('em_bookings_table_rows_col_waitlist_expiry','\EM\Waitlist\Bookings_Admin::em_bookings_table_rows_col_waitlist_expiry', 10, 3);
		add_filter('em_bookings_table_rows_col_waitlist_position','\EM\Waitlist\Bookings_Admin::em_bookings_table_rows_col_waitlist_position', 10, 3);
	}
	
	public static function em_bookings_table($EM_Bookings_Table){
		$EM_Bookings_Table->statuses['waitlist'] = array('label'=>__('Waitlist','em-pro'), 'search'=> array(6,7,8));
		$EM_Bookings_Table->status = !empty($_REQUEST['status']) && $_REQUEST['status'] == 'waitlist' ? 'waitlist' : $EM_Bookings_Table->status;
	}
	
	/**
	 * @param array $actions
	 * @param \EM_Booking $EM_Booking
	 * @return string[]
	 */
	public static function bookings_table_actions( $actions, $EM_Booking ){
		if( $EM_Booking instanceof Booking ){
			$actions = array(
				'approve' => '<a class="em-bookings-approve em-bookings-waitlist-approve" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Approve').'</a>',
				'cancel' => '<a class="em-bookings-cancel" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_cancel', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Cancel').'</a>',
				'delete' => '<span class="trash"><a class="em-bookings-delete" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Delete').'</a></span>',
				'resend_email' => '<a class="em-bookings-ajax-action em-bookings-resend-email" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'booking_resend_email', 'booking_id'=>$EM_Booking->booking_id, '_wpnonce' => wp_create_nonce('booking_resend_email_'.$EM_Booking->booking_id))).'">'.esc_html__emp('Resend Email').'</a>',
				//'edit' => '<a class="em-bookings-edit" href="'.em_add_get_params($EM_Booking->get_event()->get_bookings_url(), array('booking_id'=>$EM_Booking->booking_id, 'em_ajax'=>null, 'em_obj'=>null)).'">'.esc_html__emp('Edit/View','events-manager').'</a>',
			);
			if( $EM_Booking->booking_status == 7 ){
				unset($actions['approve']);
			}elseif( $EM_Booking->booking_status == 3 ){
				unset($actions['cancel']);
				unset($actions['resend_email']);
			}elseif( $EM_Booking->booking_status == 8 ){
				$actions['approve'] = '<a class="em-bookings-approve em-bookings-waitlist-approve" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__('Re-Approve','em-pro').'</a>';
				unset($actions['cancel']);
				unset($actions['resend_email']);
			}
		}
		return $actions;
	}
	
	public static function em_admin_event_booking_availibility( $EM_Event ){
		$waiting_spaces = Events::get_waiting_spaces($EM_Event);
		$waiting_approved_spaces = Events::get_waiting_approved_spaces($EM_Event);
		$style = 'text-decoration: underline 2px dotted #aaa; text-underline-offset: 4px;';
		if( $waiting_spaces ){
			echo ', <span class="em-tooltip" style="'.$style.'" aria-label="'. esc_html__('Number of spaces reserved when new spaces become available.', 'em-pro') .'">';
			echo esc_html__('Waitlist spaces', 'em-pro') .'</span>: ' . $waiting_spaces;
		}
		if( $waiting_approved_spaces ){
			echo ', <span class="em-tooltip" style="'.$style.'" aria-label="'. esc_html__('Number of spaces approved to make a booking from recently available spaces.', 'em-pro') .'">';
			echo esc_html__('Waitlist approved', 'em-pro') .'</span>: ' . $waiting_approved_spaces;
		}
	}
	
	public static function em_admin_event_booking_before_availibility( $EM_Event ){
		Bookings::$ignore_waitlisted = true;
	}
	
	public static function em_admin_event_booking_after_availibility( $EM_Event ){
		Bookings::$ignore_waitlisted = false;
	}
	
	/**
	 * Adds columns in the bookings tables
	 * @param array $template
	 * @return array
	 */
	public static function em_bookings_table_cols_template($template){
		$template['waitlist_expiry'] = esc_html__('Waitlist Expiry', 'events-manager-pro');
		$template['waitlist_position'] = esc_html__('Waitlist Position', 'events-manager-pro');
		return $template;
	}
	
	/**
	 * @param string $val
	 * @param \EM_Booking $EM_Booking
	 */
	public static function em_bookings_table_rows_col_waitlist_expiry($val, $EM_Booking){
		$val = '-';
		if( $EM_Booking instanceof Booking ){
			if( $EM_Booking->booking_status == 7 ){
				$EM_DateTime = new \EM_DateTime($EM_Booking->booking_meta['waitlist_expiry']);
				$val = $EM_DateTime->formatDefault();
			}elseif( $EM_Booking->booking_status == 8 ){
				$EM_DateTime = new \EM_DateTime($EM_Booking->booking_meta['waitlist_expired']);
				$val = '<span style="color:#bc8787">'. $EM_DateTime->formatDefault() . '</span>';
			}elseif( $EM_Booking->booking_status == 3 && !empty($EM_Booking->booking_meta['waitlist_expiry']) ){ // assuming it expired at all before cancellation
				$EM_DateTime = new \EM_DateTime($EM_Booking->booking_meta['waitlist_expiry']);
				if( $EM_Booking->is_expired() ) {
					$val = '<span style="color:#bc8787">' . $EM_DateTime->formatDefault() . '</span>';
				}else{
					$val = $EM_DateTime->formatDefault();
				}
			}
		}
		return $val;
	}
	
	/**
	 * @param string $val
	 * @param Booking $EM_Booking
	 */
	public static function em_bookings_table_rows_col_waitlist_position($val, $EM_Booking){
		global $wpdb;
		$val = '-';
		if( $EM_Booking instanceof Booking ){
			if( $EM_Booking->booking_status == 6 ){
				$sql = $wpdb->prepare('SELECT COUNT(*) FROM '. EM_BOOKINGS_TABLE .' WHERE event_id=%d AND booking_status=6 AND booking_id <= %d', $EM_Booking->event_id, $EM_Booking->booking_id);
				$val = $wpdb->get_var($sql);
			}
		}
		return $val;
	}
}
Bookings_Admin::init();