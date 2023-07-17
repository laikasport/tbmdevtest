<?php
namespace EM_Pro\Attendance;

/**
 * Handles functionality within the admin areas of bookings, so admins can check in users directly in admin areas and view attendance history
 */
class Booking_Admin {
	public static function init(){
		add_action('em_bookings_admin_ticket_booking_row', '\EM_Pro\Attendance\Booking_Admin::checkin_button', 1, 1);
		add_action('em_bookings_manager_template_scripts', '\EM_Pro\Attendance\Booking_Admin::em_bookings_manager_template_scripts');

		add_action('em_bookings_table_cols_template', '\EM_Pro\Attendance\Booking_Admin::em_bookings_table_cols_template',10,1);
		add_filter('em_bookings_table_rows_col_checkedin','\EM_Pro\Attendance\Booking_Admin::em_bookings_table_rows_col_checkedin', 10, 3);
		add_filter('em_bookings_table_rows_col_checkedout','\EM_Pro\Attendance\Booking_Admin::em_bookings_table_rows_col_checkedout', 10, 3);
		add_filter('em_bookings_table_rows_col_not_attended','\EM_Pro\Attendance\Booking_Admin::em_bookings_table_rows_col_not_attended', 10, 3);
	}
	
	/**
	 * Adds columns in the bookings tables
	 * @param array $template
	 * @return array
	 */
	public static function em_bookings_table_cols_template($template){
		$template['checkedin'] = esc_html__('Checked In', 'events-manager-pro');
		$template['checkedout'] = esc_html__('Checked Out', 'events-manager-pro');
		$template['not_attended'] = esc_html__('Not Attended', 'events-manager-pro');
		return $template;
	}
	
	public static function em_bookings_table_rows_col_status( $EM_Booking, $status ){
		// get a single query to sum up latest status for each ticket booking
		if( !empty($_REQUEST['ticket_id']) ){
			$bookings = Attendance::get_booking_ticket_ids_with_status( $EM_Booking, $status, $_REQUEST['ticket_id'] );
		}else{
			$bookings = Attendance::get_booking_ticket_ids_with_status( $EM_Booking, $status );
		}
		$total = $EM_Booking->get_spaces();
		$bookings = count($bookings);
		return $bookings .'/'. $total;
	}
	
	/**
	 * @param string $val
	 * @param \EM_Booking $EM_Booking
	 */
	public static function em_bookings_table_rows_col_checkedin($val, $EM_Booking){
		return static::em_bookings_table_rows_col_status( $EM_Booking, 1 );
	}
	
	/**
	 * @param string $val
	 * @param \EM_Booking $EM_Booking
	 */
	public static function em_bookings_table_rows_col_checkedout($val, $EM_Booking){
		return static::em_bookings_table_rows_col_status( $EM_Booking, 0 );
	}
	
	/**
	 * @param string $val
	 * @param \EM_Booking $EM_Booking
	 */
	public static function em_bookings_table_rows_col_not_attended($val, $EM_Booking){
		return static::em_bookings_table_rows_col_status( $EM_Booking, null );
	}
	
	public static function checkin_button( $EM_Ticket_Booking ){
		$checkin_status = Attendance::get_status($EM_Ticket_Booking);
		?>
		<div class="em-booking-single-info">
			<button type="button" class="em-clickable button-secondary with-icon attendance-action attendance-status-1 <?php if ($checkin_status !== 1) echo 'hidden'; ?>" data-action="checkout" data-uuid="<?php echo esc_attr($EM_Ticket_Booking->ticket_uuid); ?>">
				<span class="loaded em-icon em-icon-cross-circle"></span>
				<span class="loaded"><?php esc_html_e('Check Out', 'em-pro'); ?></span>
				<span class="loading-content em-icon em-icon-spinner" role="status" aria-hidden="true"></span>
				<span class="loading-content"><?php esc_html_e('Loading...', 'em-pro'); ?></span>
			</button>
			<button type="button" class="em-clickable button-secondary with-icon attendance-action attendance-status-0  attendance-status-null <?php if ($checkin_status === 1) echo 'hidden'; ?>" data-action="checkin" data-uuid="<?php echo esc_attr($EM_Ticket_Booking->ticket_uuid); ?>">
				<span class="loaded em-icon em-icon-checkmark-circle"></span>
				<span class="loaded"><?php esc_html_e('Check In', 'em-pro'); ?></span>
				<span class="loading-content em-icon em-icon-spinner" role="status" aria-hidden="true"></span>
				<span class="loading-content"><?php esc_html_e('Loading...', 'em-pro'); ?></span>
			</button>
			<span class="em-tooltip attendance-history-toggle" aria-label="<?php esc_html_e('Click to view history', 'events-manager-pro'); ?>">
				<span class="attendance-current-status attendance-display-status-<?php echo $checkin_status === null ? 'null':$checkin_status; ?> attendance-status-x">
					<?php
						echo Attendance::get_status_text($EM_Ticket_Booking);
						if( $checkin_status !== null ){
							$checkin_ts = Attendance::get_status_timestamp( $EM_Ticket_Booking );
							$EM_DateTime = new \EM_DateTime($checkin_ts);
							echo ' @ '. $EM_DateTime->formatDefault();
						}
					?>
				</span>
				<span class="attendance-status-0 hidden">
					<?php esc_html_e('Checked Out', 'events-manager-pro'); ?>
				</span>
				<span class="attendance-status-1 hidden">
					<?php esc_html_e('Checked In', 'events-manager-pro'); ?>
				</span>
				<span class="attendance-history-hidden em-icon em-icon-chevron-down"></span>
				<span class="attendance-history-visible em-icon em-icon-chevron-up"></span>
			</span>
			<div class="attendance-history">
				<table cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e_emp('When'); ?></th>
							<th scope="col"><?php esc_html_e_emp('Action'); ?></th>
						</tr>
						</thead>
					<tbody>
					<?php
					$attendance_history = Attendance::get_history($EM_Ticket_Booking);
					if( !empty($attendance_history ) ) {
						foreach ($attendance_history as $item) {
							$status_color = 'attendance-display-status-null';
							if( $item['status'] == 1 ){
								$status_color = 'attendance-display-status-1';
							}elseif( $item['status'] == 0 ){
								$status_color = 'attendance-display-status-0';
							}
							?>
							<tr>
								<td><span class="em-tooltip" aria-label="<?php echo $item['date']; ?>"><?php echo $item['time']; ?></span></td>
								<td class="<?php echo $status_color; ?>"><?php echo $item['action']; ?></td>
							</tr>
							<?php
						}
					}else{
						echo '<tr><td colspan="2"><em class="text-muted">'. esc_html__('No attendance activity.', 'em-pro') . '</em></td></tr>';
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		wp_enqueue_script('events-manager-pro-attendance', plugins_url('attendance.js',__FILE__), array(), EMP_VERSION, true);
	}
	
	public static function em_bookings_manager_template_scripts(){
		\EM_Scripts_and_Styles::localize_script(); // get the localized script here, saved in the global below, WP's localization won't ever get hit
		global $em_localized_js;
		echo 'const EM = '.json_encode($em_localized_js) . ';'."\r\n";
		include('attendance.js');
	}
}
Booking_Admin::init();