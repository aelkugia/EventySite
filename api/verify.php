<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../objects/verification.php");


$data = RestUtility::processRequest();  

switch($data->getMethod())  
{  
    case 'get':  
		$error = array('error'=>'Don\'t use GET.');
		RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
  
        break;  
    // create new verification 
    case 'post': 
    	$vars = $data->getRequestVars(); 

    	try
		{	
			$verification = new Verification( $vars['phone-number'] ); 	  
			
			$verification->createNewVerification();
			
	        $response = array('success'=>'verification created successfully.');	  
	         	  
	        // respond with the new event as JSON  
	        RestUtility::sendResponse(201, json_encode($response), 'application/json'); 			
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
		
        break;  
    // check verification    
    case 'put': 
    	$vars = $data->getRequestVars(); 

    	try
		{	
			$verification = new Verification( $vars['phone-number'] ); 	  
			
			$verification->attemptVerification( $vars['code'] );
		
	        $response = array('success'=>'verification checked successfully.');	
	         	  
	        // respond with the new event as JSON  
	        RestUtility::sendResponse(201, json_encode($response), 'application/json'); 			
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
		
        break;  
} 
?>