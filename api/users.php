<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../objects/user.php");

require_once("../objects/verification.php");

$data = RestUtility::processRequest();  

switch($data->getMethod())  
{  
    // this is a request for all events, not one in particular  
    case 'get': 
        $vars = $data->getRequestVars(); 
        	 
        if( isset($vars['id']) )
		{
			try
			{
				$user = new User( $vars['id'] );  
				
				// respond with the new event as JSON  
				RestUtility::sendResponse(200, json_encode($user), 'application/json'); 
			}
			catch(Exception $e)
			{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
			}
			
	        //$event->date = $vars['date'];    
	        //$event->setIP(getip());    
		}
		else
		{
			$error = array('error'=>'Please provide an id for the user.');
	        RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}	 
        	    
  
        break;  
    // create new user  
    case 'post': 
    	$vars = $data->getRequestVars(); 

	    try
	    {
	    	if( isset($vars['phone-number']) )
	    	{
		    	$verification = new Verification( $vars['phone-number'] ); 	  
		
				$verification->attemptVerification( $vars['code'] );
	    	}
			
        	$user = new User(); 
        	
        	if( isset( $vars['id'] ) )
        	{
        		$user = new User( $vars['id'] ); 
        	}
        	 
        	 
	        $user->name = $vars['name']; 
	        $user->email = $vars['email'];  
	        $user->setPassword( $vars['password'] );  
	        
	        if( isset($vars['phone-number']) )
			{
	        	$user->setPhoneNumber( $vars['phone-number'] ); 
	        }
	        else
	        {
		        $user->unsetPhoneNumber();
	        }
	        
	        if( isset( $vars['device-token'] ) )
        	{
	        	$user->setDeviceToken( $vars['device-token'] ); 
        	}
	        
        	$user->save(); 
        	
			if( isset($verification) )
	    	{		
				$verification->deleteVerification();
	    	}
        	
        	
			// respond with the new event as JSON  
			RestUtility::sendResponse(201, json_encode($user), 'application/json');  		        
        } 
		catch(Exception $e)
		{		        
			$error = array( 'error'=>$e->getMessage() );
			RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
        break;  
} 
?>