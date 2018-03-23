<?php
	require_once("../util/Notifier.php");
	
	$phoneNumber = '4254170408';
	
	$message = 'Test Text!';

	Notifier::sendText($phoneNumber, $message);
	//Notifier::sendPushNotification('d01ab14b2ba0fde659c88588002884690ff28fbed9e2c07ddc810127b56a16a6','Ahmed has invited you!');
?>