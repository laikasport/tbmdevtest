<?php
/**
 * @var EM_Booking $EM_Booking
 */
$EM_Event = $EM_Booking->get_event();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<titleEvents Manager Booking</title>

	<style>
		<?php
		emp_locate_template('printables/pdf-printable.css', true);
		emp_locate_template('printables/pdf-invoice/pdf-invoice.css', true);
		?>
	</style>
</head>

<body>
	<div id="content">
		<?php
		$template = emp_locate_template('printables/pdf-invoice/part-header.php');
		include($template);
		$template = emp_locate_template('printables/pdf-invoice/part-event.php');
		include($template);
		$template = emp_locate_template('printables/pdf-invoice/part-invoice.php');
		include($template);
		?>
	</div>
</body>
</html>