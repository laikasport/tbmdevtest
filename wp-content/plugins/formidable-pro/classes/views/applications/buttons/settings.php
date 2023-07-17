<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="frm_settings_button_wrapper">
	<div class="dropdown">
		<a href="#" class="frm-dropdown-toggle button frm-button-secondary" data-toggle="dropdown" role="button">
			<?php esc_html_e( 'Settings', 'formidable-pro' ); ?>
			<?php FrmAppHelper::icon_by_class( 'frmfont frm_arrowdown4_icon frm_svg13', array( 'aria-hidden' => 'true' ) ); ?>
		</a>
		<div class="frm-dropdown-menu <?php echo esc_attr( is_rtl() ? 'pull-left' : 'pull-right' ); ?>" role="menu" aria-labelledby="frm-previewDrop">
			<div class="dropdown-item">
				<h3><?php esc_html_e( 'Application settings', 'formidable-pro' ); ?></h3>
				<div>
					<label><?php esc_html_e( 'Application Name', 'formidable-pro' ); ?></label>
					<input id="frm-application-name-input" type="text" class="frm_long_input" />
				</div>
				<div>
					<button class="button frm-button-secondary">
						<?php esc_html_e( 'Cancel', 'formidable-pro' ); ?>
					</buttona>
					<button class="button frm-button-primary frm-save-application-settings">
						<?php esc_html_e( 'Save Application', 'formidable-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
