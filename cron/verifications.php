<?php
// THIS FILE RUNS EVERY MINUTE

require_once("/var/www/eventy/util/DBUtils.php");

$sql = sprintf('DELETE FROM verifications WHERE time_added < (NOW() - INTERVAL 15 MINUTE)');

try
{	
	$db = new DB('eventy');		
	
	$db->query( $sql );	
	
	error_log('Verifications Cleared Successfully!');
}
catch(Exception $e)
{
	error_log('Verification Deletion Error: ' . $e->getMessage() );
}

?>