<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../objects/event.php");

require_once("../objects/user.php");

$data = RestUtility::processRequest();  

switch($data->getMethod())  
{  
    // this is a request for all events, not one in particular  
    case 'get':  
		$error = array('error'=>'Don\'t use GET.');
		RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
  
        break;  
    // create new event  
    case 'post': 
    	$vars = $data->getRequestVars(); 

    	try
		{	
			$user = new User( $vars['user-id'] );
				
		    $event = new Event( $vars['event-id'] );  
		    		    
	        $event->attend( $user , $vars['attend-status'] , $vars['signature'] );  
	         	  
	        // respond with the new event as JSON  
	        RestUtility::sendResponse(201, json_encode($event), 'application/json'); 			
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
		
        break;  
} 
?>