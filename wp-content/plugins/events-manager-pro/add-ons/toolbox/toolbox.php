<?php
/**
 * This add-on adds powerful features to EM, such as extending cancellation options.
 */
include('cancellation.php');

// Event Submission Limits
if( get_option('dbem_event_submission_limits_enabled') ){
	include('limits.php');
}
if( is_admin() ){
	include('limits-admin.php');
}