<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

require_once("../../global/functions/getip.php");

require_once("../objects/event.php");
require_once("../objects/user.php");

$data = RestUtility::processRequest();  

switch($data->getMethod())  
{  
    // this is a request for all events, not one in particular  
    case 'get':  
    
    	$publicEventError = 'No event for those credentials';
    	try
		{
		    $vars = $data->getRequestVars(); 
		    	
		    $user = NULL;
		    
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
				    throw new Exception($publicEventError);
			    }
			    
				$event = $eventList[0];
				$fullEvent = new Event( $event->id );
				$fullEvent->attendStatus = $event->attendStatus;
				$fullEvent->type = $event->type;

				$eventList = array($fullEvent);
		    }
		    else if( isset($vars['user-id']) )
		    {
			    $user = new User( $vars['user-id'] ); 
			    $eventList = Event::getEventList(0, $user); 
		    }
		    else if( isset($vars['id']) )
		    {
			    $eventList = new Event( $vars['id'] );
		    }
		    else
		    {
			    $eventList = Event::getEventList(0, $user);
		    }
					   
			RestUtility::sendResponse(200, json_encode($eventList), 'application/json');  
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				
				if($e->getMessage() == $publicEventError)
				{
					$error['state'] = 1;  
				}
				
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
  
        break;  
    // create new event  
    case 'post': 
    	$vars = $data->getRequestVars(); 

    	try
		{
			if( isset($vars['user-id']) && isset($vars['name']) && isset($vars['date']) && isset($vars['signature']) )
			{
				$user = new User( $vars['user-id'] ); 
				
				$actualSignature = hash('sha512', $user->getPassword() . $user->id . $vars['name']);

				if( $vars['signature'] == $actualSignature )
				{
					$event = new Event();  
		    
			        $event->setName( $vars['name'] );  
			        $event->date = $vars['date'];    
			        $event->attendees = $vars['attendees']; 
			        
			        $event->location = $vars['location']; 
			        $event->description = $vars['description']; 
			        
					if( isset($vars['isPublic']) && ($vars['isPublic']) == 1 )
			        {
				        $event->makePublic();
			        } 
			        
			        //$event->setIP(getip());    
			         
			        if( isset($vars['user-id']) )
			        {
				        $event->setUserId( $vars['user-id'] );
			        } 
			         
			        $event->save();  
			  
			        // respond with the new event as JSON  
			        RestUtility::sendResponse(201, json_encode($event), 'application/json'); 
				}
				else
				{
					throw new Exception('Incorrect Signature.');
				}	
			}	
			else
			{
				throw new Exception('Incomplete POST request.');
			}		
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
		
        break;  
} 
?>