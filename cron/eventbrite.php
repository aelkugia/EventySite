<?php

require_once("/var/www/eventy/util/DBUtils.php");
require_once("/var/www/eventy/objects/event.php");

$cities = array();
$cities[] = 'redmond';
$cities[] = 'seattle';
$cities[] = 'los_angeles';
$cities[] = 'san_francisco';
$cities[] = 'chicago';
$cities[] = 'miami';
$cities[] = 'new_york';
$cities[] = 'vancouver';
$cities[] = 'boston';
$cities[] = 'vegas';

foreach($cities as $city)
{
	loadEvents($city);	
	echo 'Added events from ' . $city . '<br>';
}

function addEventsToEventy($events)
{
	$eventbriteEvents = array();

	$eventbriteCategories = array();
	
	$db = new DB('eventy');		
	
	$eventyCategories = array();
	
	foreach($events as $eventB)
	{
		unset($eventB->description->html);
		unset($eventB->organizer->description->html);
		
		if( $eventB->category == NULL || $eventB->category->short_name == '')
		{
			$eventB->category->short_name = 'Other';
		}
		
		// add to list of events to add to Eventy
		$eventbriteEvents[ intval($eventB->id) ] = $eventB;
			
		if($eventB->category->short_name == 'Other') 
		{
			$eventbriteCategories[ $db->getSQLValueString('All','text') ] = 0;
			$eventB->category->short_name = 'All';
		}
		else
		{
			$eventbriteCategories[ $db->getSQLValueString($eventB->category->short_name,'text') ] = 0;
		}
	}
	
	// Find all categories that exist in Eventy category database
	try
	{
		$sql = sprintf('SELECT categories.* FROM categories WHERE name IN (%s);', implode(',', array_keys($eventbriteCategories) ) );

		$result = $db->query( $sql );
		
		while( $eventCategory = $result->fetch_assoc() )
		{
			$eventyCategories[ $db->getSQLValueString($eventCategory['name'],'text') ] = intval($eventCategory['id']);
			unset($eventbriteCategories[ $db->getSQLValueString($eventCategory['name'],'text') ]);
		}
	}
	catch(Exception $e)
	{
		echo 'Error selecting categories: ' . $e->getMessage();
		exit();
	}
	
	$eventbriteCategories = array_keys($eventbriteCategories);
	
	if(count($eventbriteCategories) > 0)
	{
		// Add new categories
		try
		{
			foreach( $eventbriteCategories as $category )
			{
				$sql = sprintf('INSERT INTO categories (name) VALUES (%s);', $category );
		
				$db->query( $sql );
		
				$eventyCategories[ $category ] = intval( $db->insert_id );
				unset($eventbriteCategories[ $db->getSQLValueString($eventCategory['name'],'text') ]);
			}
		}
		catch(Exception $e)
		{
			echo 'Error selecting categories: ' . $e->getMessage();
			exit();
		}
	}
	
	try
	{
		$sql = sprintf('SELECT eventbrite_events.* FROM eventbrite_events WHERE eventbrite_id IN (%s);', implode(',', array_keys($eventbriteEvents) ) );
		
		$result = $db->query( $sql );
		
		while( $eventRow = $result->fetch_assoc() )
		{
			unset($eventbriteEvents[ intval($eventRow['eventbrite_id']) ]);
		}
	}
	catch(Exception $e)
	{
		echo 'Error selecting ids: ' . $e->getMessage();
		exit();
	}
	
	$eventbriteMapping = array();
	
	foreach($eventbriteEvents as $eventbriteId => $eventB)
	{
		if( $eventB->category != NULL )
		{
			try
			{
				if(intval($eventB->venue->latitude) == 0 && intval($eventB->venue->longitude) == 0)
				{
					// don't add dummy events at (0,0)
					// There is nothing going on south of Ghana
					continue;
				}
				
				$event = new Event();  
				
				$event->setName( $eventB->name->text );  
				$event->date = $eventB->start->local;    
				
				$event->location['latitude'] = $eventB->venue->latitude;
				$event->location['longitude'] = $eventB->venue->longitude; 
				$address = sprintf('%s %s,%s',$eventB->venue->address->address_1 , $eventB->venue->address->city, $eventB->venue->address->region);
				$event->location['string'] = isset($eventB->venue->name) && $eventB->venue->name != '' ? $eventB->venue->name . ' ' . $address : 'Unknown'; 
		 
		 		$event->description = 'Buy tickets: ' . $eventB->url . ' '; 
				$event->description .= isset($eventB->description->text) && $eventB->description->text != '' ? strip_tags($eventB->description->text) : ' '; 
				
				$event->category = $eventB->category->short_name;
				
				if(isset($eventB->logo->url))
				{
					$event->image = $eventB->logo->url;
				}
				
				$event->makePublic();
				
				$events[] = $event; 
				$event->save(); 
				
				$eventbriteMapping[] = '('. $eventbriteId .','. $event->id .')';
			}
			catch(Exception $e)
			{			
				echo json_encode($events);
				
				error_log($e->getMessage());
				
				exit();
			}
			
		}
	}
	
	if( count($eventbriteMapping) > 0 )
	{
		try
		{
			$sql = sprintf("INSERT IGNORE INTO eventbrite_events (eventbrite_id,event_id) VALUES %s;" , implode(',', $eventbriteMapping) );			
			$db->query( $sql );		
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
		}
	}

}

function loadEvents( $city )
{
	$url = sprintf("https://www.eventbriteapi.com/v3/events/search/?token=J7QT5K4L4NQ4P6S6TECJ&venue.city=" . $city);
		
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	
	$response = json_decode($output);
						
	$pagination = $response->pagination;					
		
	$events = $response->events;	
	
	addEventsToEventy($events);			
				
	for($i = 5; $i <= $pagination->page_count; $i++)
	{
		$url = sprintf("https://www.eventbriteapi.com/v3/events/search/?token=J7QT5K4L4NQ4P6S6TECJ&venue.city=" . $city . "&page=" . $i);
				
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		
		$response = json_decode($output);
									
		$events = $response->events;	
		
		echo 'Adding ' . count($events) . ' events for city ' . $city . ' on page ' . $i . '...'; 	

		if( isset($events) && count($events) > 0 )
		{		
				addEventsToEventy($events);	
				echo 'Done';	
		}
		echo '<br>';		
	}
	
	curl_close($ch);
}

?>

