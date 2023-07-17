<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
$url = admin_url( 'admin.php?page=formidable-applications' );
?>
<div class="frm-full-close">
	<a href="<?php echo esc_url( $url ); ?>" aria-label="Close">
		<svg class="frmsvg" aria-label="Dismiss">
			<use xlink:href="#frm_close_icon"></use>
		</svg>
	</a>
</div>
