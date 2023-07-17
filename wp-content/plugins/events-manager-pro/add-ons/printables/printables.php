<?php
use Dompdf\Dompdf;

if( is_admin() ){
	include('printables-admin.php');
}
if( get_option('dbem_bookings_pdf') ){
	include('printables-pdfs.php');
}