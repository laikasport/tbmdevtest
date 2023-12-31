<?php
namespace EM_Pro;
class Printables_Admin {
	
	public static function init(){
		add_action('em_options_page_footer_bookings', '\EM_Pro\Printables_Admin::options');
	}
	
	/*
	 * --------------------------------------------
	 * Email Reminders
	 * --------------------------------------------
	 */
	/**
	 * Generates meta box for settings page
	 */
	public static function options(){
		global $save_button;
		?>
		<div  class="postbox " id="em-opt-printables" >
			<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle', 'events-manager'); ?>"><br /></div><h3><?php _e ( 'Printables (PDFs, Invoices, Tickets etc.)', 'em-pro' ); ?></h3>
			<div class="inside">
				<table class='form-table'>
					<tr class="em-boxheader"><td colspan='2'>
							<p>
								<?php
								_e( 'You can include invoices and tickets in PDF attachments which are added automatically to their confirmation email. Below are options on what to send, and some customization options for your headers.', 'em-pro' );
								//You can further customize all these templates, or parts of them by overriding our template files as per our %s.
								?>
							</p>
						</td></tr>
					<?php
					em_options_radio_binary ( sprintf(_x( 'Enable %s?', 'Enable a feature in settings page', 'em-pro' ), 'PDFs'), 'dbem_bookings_pdf','', '', '.booking-pdf-options');
					?>
					<tbody class="booking-pdf-options">
						<?php
						em_options_radio_binary ( __( 'Include Invoices in Emails?', 'em-pro' ), 'dbem_bookings_pdf_email_invoice',__('A PDF will be attached in the automated confirmation email containing an invoice.', 'em-pro'));
						em_options_radio_binary ( __( 'Include Tickets in Emails?', 'em-pro' ), 'dbem_bookings_pdf_email_tickets',__('A PDF will be attached in the automated confirmation email containing a booking summary as well as individual tickets. Enable QR codes in the Ticket Scanning optinos to include them in your tickets.', 'em-pro'));
						global $bookings_placeholder_tip;
						em_options_input_text ( __( 'Invoice Number Format', 'em-pro' ), 'dbem_bookings_pdf_invoice_format',__('You can modify the format of your invoice numbers such as adding prefixes, mixing numbers etc.', 'em-pro'). '<br>'.$bookings_placeholder_tip, 1);
						$pdf_logo = get_option('dbem_bookings_pdf_logo');
						$pdf_logo_id = get_option('dbem_bookings_pdf_logo_id');
						?>
						<tr class="form-field pdf-image-wrap">
							<th scope="row" valign="top"><label for="pdf-image"><?php esc_html_e('Logo','events-manager'); ?></label></th>
							<td>
								<div class="img-container">
									<?php if( !empty($pdf_logo) ): ?>
										<img src="<?php echo $pdf_logo; ?>" />
									<?php endif; ?>
								</div>
								<input type="text" name="dbem_bookings_pdf_logo" id="pdf-image" class="img-url" value="<?php echo esc_attr($pdf_logo); ?>" />
								<input type="hidden" name="dbem_bookings_pdf_logo_id" id="pdf-image-id" class="img-id" value="<?php echo esc_attr($pdf_logo_id); ?>" />
								<p class="hide-if-no-js">
									<input id="upload_image_button" type="button" value="<?php esc_html_e_emp('Choose/Upload Image'); ?>" class="upload-img-button button-secondary" />
									<input id="delete_image_button" type="button" value="<?php esc_html_e_emp('Remove Image'); ?>" class="delete-img-button button-secondary" <?php if( empty($pdf_logo) ) echo 'style="display:none;"'; ?> />
								</p>
								<br />
								<p class="description"><?php echo __('This image will be displayed on top of your invoice, leave blank to use text defined below instead.','em-pro'); ?></p>
							</td>
						</tr>
						<?php
						em_options_input_text ( __( 'Alternate Logo Text', 'em-pro' ), 'dbem_bookings_pdf_logo_alt',__('If no logo is defined, this text will be used instead as the heading of your invoices and tickets.', 'em-pro'), 1);
						em_options_textarea( __( 'Billing Details Text', 'em-pro' ), 'dbem_bookings_pdf_billing_details',__('This is dynamic information generated by your customer and obtained during bookings or in their user profile. HTML is accepted, line breaks are respected.', 'em-pro'). '<br>'.$bookings_placeholder_tip);
						em_options_textarea( __( 'Business Details Text', 'em-pro' ), 'dbem_bookings_pdf_business_details',__('This is your business information and appears on both invoices and ticket bookings. HTML is accepted, line breaks are respected.', 'em-pro'));
						?>
					</tbody>
					<?php echo $save_button; ?>
				</table>
			</div> <!-- . inside -->
		</div> <!-- .postbox -->
		<script>
			<?php
				wp_enqueue_media();
				wp_enqueue_script( 'em-printables-admin', '', array('jquery','media-upload','thickbox','farbtastic','wp-color-picker'), false, true );
				include(dirname(__FILE__).'/printables-pdf-admin.js');
			?>
		</script>
		<?php
	}
}
Printables_Admin::init();