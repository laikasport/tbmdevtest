<?php
if( get_option('dbem_automation_enabled') ){
	include('automation.php');
}
if( is_admin() ){
	include('admin/admin.php');
}