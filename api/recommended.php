<?php

//shared by all websites 
require_once("../../global/util/RestUtility.php");

//shared between websites below ahmedjafri.com
require_once("../../global/functions/getip.php");

require_once("../objects/event.php");

$data = RestUtility::processRequest();  

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}

switch($data->getMethod())  
{  
    // this is a request for all events, not one in particular  
    case 'get':  
    	try
		{
		    $vars = $data->getRequestVars(); 
		    	
		    $eventList = array();	
		    	
			if( !isset($vars['latitude']) || !isset($vars['longitude']) )
			{
				throw new Exception('Turn On Location Services to Allow "Eventy" to Determine Your Location.');	
			}
				
		    if( $vars['category'] )
		    {
				$user = NULL;
				
				if($vars['user-id'])
				{
					$user = new User( $vars['user-id'] ); 
				}
				
		    	foreach($vars['category'] as $category)
		    	{					
			    	$eventList = array_merge($eventList, Event::getRecommendedEventList(0, $category, $vars['latitude'], $vars['longitude'], $user ) ); 
		    	}
		    }	
		    else
		    {
			    $eventList = array(); //Event::getRecommendedEventList(0); 
		    }
					   
			RestUtility::sendResponse(200, json_encode(utf8ize($eventList), JSON_UNESCAPED_UNICODE), 'application/json');  
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