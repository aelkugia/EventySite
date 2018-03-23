<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../util/DBUtils.php");

require_once("../objects/event.php");
require_once("../objects/user.php");

$data = RestUtility::processRequest();  

switch($data->getMethod())  
{  
    case 'get': 
		$error = array('error'=>'GET not supported.');
		RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
        break;  
    case 'post':  
    	try
		{
			$vars = $data->getRequestVars(); 

		    if( isset($vars['user-id']) && isset($vars['id']) )
		    {
			    $user = new User( $vars['user-id'] ); 
			    $events = Event::getEventList(0, $user); 
			    
			    $eventList = array();
			    
			    foreach($events as $event)
			    {
				    if( intval($event->id) == intval($vars['id']) )
				    {
					    $eventList[] = $event;
				    }
			    }
			    
			    // if empty throw an error
			    if( count($eventList) == 0)
			    {
				    throw new Exception('You need to attend the event to invite more friends.');
			    }
			    else
			    {
				    $event = $eventList[0];
				    
				    if( isset( $vars['attendees'] ) )
				    {		
				    	if( $event->isPublic() >= 1 )
					    {	
					    	$db = new DB('eventy');
	
						    $attendeeRows = $event->addAttendees( $vars['attendees'] , $user->id);
						    
						    $insertNotifierParams = array();
						    //send notification to each attendee
							foreach( $attendeeRows as $attendee )
						    {	
								$insertNotifierParams[] = '(' . $db->getSQLValueString($user->id,'int') . ',' . $db->getSQLValueString($attendee['id'],'int') . ',' . $db->getSQLValueString($event->id,'int') .  ')';
						    }
					    
						    $sql = sprintf("INSERT IGNORE INTO subscribers (subscriber_id,notifier_id,event_id) VALUES %s;" , implode(',', $insertNotifierParams) );
						
							$db->query( $sql );
						
							$db->close();
					    }	
					    else
					    {
						    throw new Exception('Event is private. Cannot invite more friends.');
					    }
				    }
				    else
				    {
					    //throw new Exception('No attendees given.');
				    }
				    
			    }
		    }
		    else
		    {
			    throw new Exception('Please provide a proper event and user.');
		    }
					   
			RestUtility::sendResponse(200, json_encode($eventList), 'application/json');  
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
  
        break;  
} 
?>