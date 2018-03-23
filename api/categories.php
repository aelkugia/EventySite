<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../util/DBUtils.php");

$data = RestUtility::processRequest();  

switch($data->getMethod())  
{  
    // this is a request for all events, not one in particular  
    case 'get':  
    	try
		{
		    $sql = sprintf('SELECT * FROM categories');
		    
		    $result = DBUtils::execute( $sql );
			
			$categories = array();
			
			while( $categoryRow = $result->fetch_assoc() )
			{
				$category = array();
				$category['name'] = $categoryRow['name'];
				$category['id']   = $categoryRow['id'];
				
				$categories[] = $category;
			}
					   
			RestUtility::sendResponse(200, json_encode($categories), 'application/json');  
		}
		catch(Exception $e)
		{
				$error = array('error'=>$e->getMessage());
				RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
		}
  
        break;  
    // create new event  
    case 'post': 
		$error = array('error'=>'POST not supported.');
		RestUtility::sendResponse(400, json_encode($error), 'application/json'); 
        break;  
} 
?>