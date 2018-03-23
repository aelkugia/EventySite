<?php
// THIS FILE RUNS EVERY MINUTE

require_once("/var/www/eventy/util/DBUtils.php");

$db = new DB('eventy');		

$sql = sprintf('SELECT events.* FROM events WHERE event_time < (NOW() - INTERVAL 2 DAY);');

try
{		
	$result = $db->query( $sql );	
		
	$ids = array();
	
	if($result->num_rows > 0)
	{
		while( $eventRow = $result->fetch_assoc() )
		{
			$ids[] = $db->getSQLValueString( $eventRow['id'] ,'int');
		}
			
		$sql = sprintf('DELETE FROM attendees WHERE eventy_id IN (%s);', implode(',', $ids) );	
		$db->query( $sql );
				
		$sql = sprintf('DELETE FROM user_events WHERE eventy_id IN (%s);', implode(',', $ids) );	
		$db->query( $sql ); 
		
		$sql = sprintf('DELETE FROM eventbrite_events WHERE event_id IN (%s);', implode(',', $ids) );	
		$db->query( $sql ); 
		
		$sql = sprintf('DELETE FROM events WHERE id IN (%s);', implode(',', $ids) );	
		$db->query( $sql ); 
		
		$sql = sprintf('DELETE FROM subscribers WHERE event_id IN (%s);', implode(',', $ids) );	
		$db->query( $sql );
		
		$sql = sprintf('DELETE FROM comments WHERE eventy_id IN (%s);', implode(',', $ids));	
		$db->query( $sql );
	}
	
	error_log('Events Cleared Successfully!');
}
catch(Exception $e)
{
	error_log('Events Deletion Error: ' . $e->getMessage() );
}

?>