<?php
/**
 * Restrict direct access.
 *
 * @package frmsig
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="error">
	<p>
		<?php
		esc_html_e( 'Formidable Digital Signatures requires that Formidable Forms version 3.0 or greater be installed. Until then, keep Formidable Digital Signatures activated only to continue enjoying this insightful message.', 'frmsig' );
		?>
	</p>
</div>
