<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../objects/user.php");

$data = RestUtility::processRequest();  

switch($data->getMethod())  
{    
    case 'get': 
		$error = array('error'=>'Please POST user info.');
	    RestUtility::sendResponse(400, json_encode($error), 'application/json'); 	     
        break;
          
    // attempt to login user
    case 'post': 
    	$vars = $data->getRequestVars(); 

		if( isset($vars['email']) && isset($vars['password']) )
		{
			$user = new User();  
	        $user->email = $vars['email'];  
	        $user->setPassword( $vars['password'] );  
	         
	        try
	        {
	        	$user->attemptLogin();
				RestUtility::sendResponse(201, json_encode($user), 'application/json');   		        
	        } 
			catch(Exception $e)
			{
				$error = array( 'error'=>$e->getMessage() );
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
			} 
		}
		else
		{
			$error = array('error'=>'Please provide an email and password.');
	        RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
    	
 
        
        break;  
} 
?>