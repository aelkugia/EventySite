<?php
require_once("../util/DBUtils.php");

require_once("../util/Notifier.php");
require_once("/var/www/global/DB.php");

require_once("user.php");

date_default_timezone_set('America/Vancouver');

class Event 
{
	public $id;
	public $name; // name of the Event
	public $date; // date and time of the Event
	public $location; // location of the Event - array containing (latitude & longitude)
	private $userId;
	public $attendees;
	public $attendStatus;
	public $type;
	public $description;
	public $isPublic; // 0 is private, 1 is public
	public $category;
	public $distance;
	public $image;
	public $commentCount;
	
	public function __construct($eventId=0) 
	{ 
		if( $eventId == 0 )
		{
			$name = "Empty Event";
		}
		else
		{
			$this->id = intval( $eventId );
			
			try
			{
				$this->loadEventFromDB();
			}
			catch(Exception $e)
			{
				throw new Exception('Event not found.');
			}
		}
	} 
	
	public function setName( $name )
	{
		if( $name != NULL && strlen($name) > 0)
		{
			$this->name = $name;
		}
		else
		{
			throw new Exception('Please enter name.');
		}
	}
	
	public function makePublic()
	{
		$this->isPublic = 1;
	}
	
	public function isPublic()
	{
		return $this->isPublic;
	}
	
	public function setUserId( $userId )
	{
		$this->userId = $userId;
	}
	
	public function getUserId()
	{
		return $this->userId;
	}
	
	public function attend( $user = NULL , $attendStatus = NULL, $signature = NULL)
	{
		if( $user != NULL && $signature != NULL && $attendStatus != NULL)
		{
			$actualSignature = hash('sha512', $user->getPassword() . $user->id . $attendStatus);
									
			if($actualSignature == $signature)
			{				
				$attendStatus = intval($attendStatus);
					
				$sql = sprintf('UPDATE attendees SET attend_status=%d WHERE user_id = %d AND eventy_id = %d;',$attendStatus, $user->id, $this->id);
				
				$db = new DB('eventy');
				
				$db->query( $sql );
								
				if( $db->affected_rows == 0 )
				{
					if( intval($this->isPublic) > 0 )
					{
						$sql = sprintf('INSERT INTO attendees (user_id,eventy_id,attend_status) VALUES (%d,%d,%d);', $user->id, $this->id, $attendStatus);
						
						try
						{
							$result = DBUtils::execute($sql); 
						}
						catch(Exception $e)
						{
							
						}
				
					}
				}
								
				if( intval($attendStatus) == 1 )
				{		
					$eventUser = new User( $this->userId );
		
					$sql = sprintf('SELECT subscribers.*, subscriber.device_token, subscriber.name sname, notifier.name nname FROM subscribers ' .
									'LEFT JOIN users AS subscriber ON subscriber.id=subscribers.subscriber_id ' .
									'LEFT JOIN users AS notifier ON notifier.id=subscribers.notifier_id WHERE notifier_id=%d AND event_id=%d',$user->id , $this->id);
								
					$result = DBUtils::execute($sql); 
										
					if( $result->num_rows > 0 )
					{
						while( $subscriberRow = $result->fetch_assoc() )
						{
							$message = $subscriberRow['nname'] . ' (invited by '. $subscriberRow['sname'] .') is attending ' . $this->name . '!';
											
							Notifier::sendPushNotification($subscriberRow['device_token'], $message, array('event_id'=>$this->id) );	
							
							Notifier::sendPushNotification($eventUser->getDeviceToken(), $message, array('event_id'=>$this->id) );	
						}
						
					}
					else
					{
						$message = $user->name . ' is attending ' . $this->name . '!';
												
						Notifier::sendPushNotification($eventUser->getDeviceToken(), $message, array('event_id'=>$this->id) );	
					}

				}
			}
			else
			{
				throw new Exception('Signature is wrong.');
			}
		}
		else
		{
			throw new Exception('Please provide a valid user.');
		}
	}
	
	public function delete( $user = NULL , $signature = NULL)
	{
		$salt = '';

		if( $user != NULL && $signature != NULL)
		{
			$actualSignature = hash('sha512', $user->getPassword() . $user->id . $salt);
									
			if($actualSignature == $signature)
			{				
				if( intval($user->id) == intval($this->userId) )
				{	
					$sql = sprintf('DELETE FROM attendees WHERE eventy_id=%d',$this->id);	
					DBUtils::execute($sql); 
							
					$sql = sprintf('DELETE FROM user_events WHERE eventy_id=%d',$this->id);	
					DBUtils::execute($sql); 
					
					$sql = sprintf('DELETE FROM subscribers WHERE event_id=%d',$this->id);	
					DBUtils::execute($sql); 
					
					$sql = sprintf('DELETE FROM events WHERE id=%d',$this->id);	
					DBUtils::execute($sql); 
				}
				else
				{	
					$sql = sprintf('DELETE FROM attendees WHERE eventy_id=%d AND user_id=%d',$this->id, $user->id);	
					$result = DBUtils::execute($sql); 
				}
			}
			else
			{
				throw new Exception('Signature is wrong.');
			}
		}
		else
		{
			throw new Exception('Please provide a valid user.');
		}
	}
	
	public function save() 
	{ 
		$db = new DB('eventy');

		$params = array();
		$params['events.name'] = $db->getSQLValueString($this->name,'text');

		if( isset($this->date) )
		{
			$params['events.event_time'] = $db->getSQLValueString($this->date,'text');
		}

		if( isset($this->location) )
		{
			$params['events.location_lat'] = $db->getSQLValueString($this->location['latitude'],'double');
			$params['events.location_long'] = $db->getSQLValueString($this->location['longitude'],'double');
			$params['events.location_string'] = $db->getSQLValueString($this->location['string'],'text');
		}
		
		if( isset($this->description) )
		{
			$params['events.description'] = utf8_encode($db->getSQLValueString($this->description ,'text'));
		}
		
		if( isset($this->isPublic) && $this->isPublic == 1 )
		{
			$params['events.public_private'] = 1;
		}

		if( isset($this->id) )
		{
			$params['id'] = $this->id;
		}
		
		if( isset($this->image) )
		{
			$params['events.image'] = $db->getSQLValueString($this->image,'text');
		}
			
		if( isset($this->category) )
		{
			$params['events.category_id'] = sprintf('(SELECT categories.id FROM categories WHERE categories.name=%s)', $db->getSQLValueString($this->category ,'text'));
		}	
			
		$update = array();
		
		foreach ($params as $key => $value) 
		{
			$update[] = $key . '=' . $value;
		}
		
		$sql = sprintf("INSERT INTO events (%s) VALUES (%s);" , implode(',',array_keys($params)) , implode(',',array_values($params)));			
		
		$db->query( $sql );
				
		$this->id = $db->insert_id;
		
		if( isset($this->userId) )
		{
			$sql = sprintf("INSERT INTO user_events (user_id,eventy_id) VALUES (%d,%d);" , 
							$db->getSQLValueString($this->userId,'int'), $db->getSQLValueString($this->id,'int'));
							
			$db->query( $sql );
		}
		
		if( isset($this->attendees) )
		{
			$this->addAttendees( $this->attendees );
		}
				
		$db->close();
		
		$this->loadEventFromDB();
	} 
	
	public function addAttendees( $attendees , $userId=0 )
	{
			$db = new DB('eventy');

			$phoneNumbers = array();
			$insertAttendeeParams = array(); 
			
			//create insert query
			foreach ($attendees as $attendeeString )
			{
				$phoneNumber = substr($attendeeString, 0, 10);
				
				$name = substr($attendeeString, 10);
				
				$phoneNumbers[] = $db->getSQLValueString($phoneNumber,'text');
				
				$insertAttendeeParams[] = '(' . $db->getSQLValueString($phoneNumber,'text') . ',' . $db->getSQLValueString($name,'text') .')';
			}
						
			$sql = sprintf("INSERT IGNORE INTO users (phone_number,name) VALUES %s;" , implode(',', $insertAttendeeParams) );	
										
			$db->query( $sql );			
						
			$sql = sprintf("SELECT users.* FROM users WHERE users.phone_number IN (%s);" , implode(',', $phoneNumbers) );	

			$result = $db->query( $sql );
						
			if($userId == 0)
			{			
				$user = new User( $this->userId );
			}
			else
			{
				$user = new User( $userId );
			}
			
			$datetime = strtotime($this->date);

			$message = sprintf('%s invited you to %s on %s at %s!', $user->name, $this->name, date('F j, Y, g:i a',$datetime), $this->location['string']);

			$message .= sprintf(' Download Eventy to stay connected with %s and friends to Create, Share and Discover events near you! http://goo.gl/h9ULHp .', $user->name);

			$attendeeRows = array();

			$sql = sprintf("INSERT INTO attendees (user_id, eventy_id) SELECT users.id, %d FROM users WHERE phone_number IN (%s);" , $this->id, implode(',', $phoneNumbers) );
																
			$db->query( $sql );

			//send notification to each attendee
			while( $attendee = $result->fetch_assoc() )
		    {	
		    	$attendeeRows[] = $attendee;
		    	
		    	$deviceToken = $attendee['device_token'];

		    	if( strlen($deviceToken) <= 1 || intval($attendee['id']) == 45)
		    	{
		    		error_log('Sent text to ' . $attendee['phone_number'] . ' with device token: ' . $deviceToken );
			    	Notifier::sendText($attendee['phone_number'], $message );
		    	}
		    	else
		    	{
			    	Notifier::sendPushNotification($deviceToken, $message, array('event_id'=>$this->id));	
		    	}
		    }
			
			$db->close();
			
			return $attendeeRows;
	}
	
	// Table row to object converter
	public static function getEventFromArray($event, $mysqlArray='')
	{
		if(!empty($mysqlArray))
		{
			$event->id = $mysqlArray['id'];
			$event->name = $mysqlArray['name'];
			
			$event->location = array();
			$event->location['latitude'] = $mysqlArray['location_lat']; 
			$event->location['longitude'] = $mysqlArray['location_long']; 
			$event->location['string'] = $mysqlArray['location_string']; 
			$event->description = utf8_encode($mysqlArray['description']);
			$event->category = $mysqlArray['category']; 
			$event->isPublic = intval($mysqlArray['public_private']); 
			$event->image = $mysqlArray['image'];

			if( isset($mysqlArray['attend_status']) )
			{
				$event->attendStatus = intval($mysqlArray['attend_status']);
			} 
			
			if( isset($mysqlArray['distance']) )
			{
				$event->distance = $mysqlArray['distance'];
			} 
		
			if (isset($mysqlArray['commentCount'])) 
			{
				$events[$count]->commentCount = intval($mysqlArray['commentCount']);
			}

			$date = new DateTime($mysqlArray['event_time']);
			$event->date = $date->format('c');//$mysqlArray['event_time'];			
			
			$db = new DB('eventy');
			$sql = sprintf('SELECT id FROM comments WHERE eventy_id=%d', $db->getSQLValueString($event->id,'int'));	
			$result = $db->query($sql); 
			
			$event->commentCount = $result->num_rows;
			return $event;
		}
	}

	public static function getEventList($page , $user=NULL)
	{
		$db = new DB('eventy');

		$limit = 100;
		
		$userFilter = '';
			
		// get hosted events		
		if($user != NULL)
		{
			$sql = sprintf(	'SELECT events.*, categories.name category FROM user_events '. 
							'LEFT JOIN events ON events.id = user_events.eventy_id '. 
							'LEFT JOIN categories ON events.category_id = categories.id '. 
							'WHERE user_events.user_id=%d '.
							'ORDER BY event_time DESC LIMIT %d , %d;', $db->getSQLValueString($user->id,'int'), $page, $limit);	
		}
		else
		{
			$sql = sprintf(	'SELECT events.*,categories.name category, attendees.attend_status FROM user_events, events ' . 
							'LEFT JOIN categories ON events.category_id = categories.id ' .
							'ORDER BY event_time DESC LIMIT %d , %d;', $page, $limit);
		}
						
		$result = $db->query( $sql );
						
		$events = Event::getEventsFromResult($result, 'mine');
        
        
        // get invited events
        if( $user != NULL )
        {
	        $sql = sprintf(	'SELECT events.*,categories.name category, attendees.attend_status FROM attendees '. 
							'LEFT JOIN events ON events.id = attendees.eventy_id '. 
							'LEFT JOIN categories ON events.category_id = categories.id '. 
							'WHERE attendees.user_id=%d '.
							'ORDER BY event_time DESC LIMIT %d , %d;', $db->getSQLValueString($user->id,'int'), $page, $limit);	
        }
        
        $result = $db->query( $sql );
        
        $events = array_merge($events, Event::getEventsFromResult($result, 'invited') );

        $db->close(); 
        
        return $events;
	}
	
	public static function getRecommendedEventList($page, $category=0, $latitude=0, $longitude=0, $user = NULL)
	{
		$db = new DB('eventy');

		$limit = 100;
		
		$categoryFilter = '';
		$locationFilter = '';
		$locationTruncate = '';
		$locationOrder = 'ORDER BY event_time ASC';
	
		if( $category != 0 )
		{
			$categoryFilter = sprintf('AND category_id=%d ', $category);
		}
		
			$locationFilter = sprintf(',( 6371 * acos( cos( radians(%f) ) * cos( radians( events.location_lat ) ) * cos( radians( events.location_long ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( events.location_lat ) ) ) ) AS distance',$latitude,$longitude,$latitude);
			$locationTruncate = sprintf('HAVING distance <= %d ',100);
			// $locationOrder = 'ORDER BY distance ASC';
		
		$sql = sprintf(	'SELECT events.*,attendees.attend_status, categories.name category ' . $locationFilter . ' FROM events ' . 
							'LEFT JOIN categories ON events.category_id = categories.id ' .
							'LEFT JOIN attendees ON events.id = attendees.eventy_id AND attendees.user_id = %d ' .
							'WHERE public_private=2 ' .
							$categoryFilter . $locationTruncate . $locationOrder .
							' LIMIT %d , %d;', $user->id , $page, $limit);
										
		$result = $db->query( $sql );
						
		$events = Event::getEventsFromResult($result, 'public');

        $db->close(); 
        
        return $events;
	}
	
	private static function getEventsFromResult($result,$type="Unknown")
	{
		$events = array(); //create a map from id's to spot objects
		
		$count = 0;
		
        while( $event = $result->fetch_assoc() )
        {
        	
        	$events[$count] = new Event();
        	
        	$events[$count]->type = $type;
        	
        	if( $type == 'mine' )
        	{
        		$events[$count]->attendStatus = 6; //this is hardcoded in the iOS App.
        	}
						
        	$events[$count] = Event::getEventFromArray($events[$count], $event);
        	$count++;
        }
                
        return array_values($events);
	}
	
		// Table row to object converter
	private function loadEventFromDB()
	{
		if( isset($this->id) )
		{
			$sql = sprintf("SELECT events.*" 
			. " FROM events WHERE events.id = %d;", $this->id);
												
			$result = DBUtils::execute($sql); 
			
			if($result->num_rows > 0)
			{
				$eventRow = $result->fetch_assoc();
			
				$this->name = $eventRow['name'];
				
				$this->location = array();
				$this->location['latitude'] = $eventRow['location_lat']; 
				$this->location['longitude'] = $eventRow['location_long']; 
				$this->location['string'] = $eventRow['location_string']; 
				$this->description = utf8_encode($eventRow['description']); 
				$this->isPublic = intval($eventRow['public_private']); 
				$this->image = $eventRow['image'];

				$this->date = $eventRow['event_time'];	
				
				$sql = sprintf('SELECT users.*, attendees.attend_status FROM attendees '. 
							'LEFT JOIN users ON users.id = attendees.user_id '. 
							'WHERE attendees.eventy_id=%d', $this->id);
				
				$result = DBUtils::execute($sql); 
							
				$this->attendees = array();
		
		        while( $attendee = $result->fetch_assoc() )
		        {
		        	$attendeeObject = array();
		        	$attendeeObject['hash'] = hash('sha512', $attendee['phone_number']);
					$attendeeObject['status'] = intval($attendee['attend_status']);
					
					if(!is_null($attendee['name']))
						$attendeeObject['name'] = $attendee['name'];
					else
						$attendeeObject['name'] = "Deleted User";
					
					$this->attendees[] = $attendeeObject;
		        }		
		     
		        //get user id
		        
		        $sql = sprintf('SELECT user_events.* FROM user_events WHERE user_events.eventy_id=%d',$this->id);
		        $result = DBUtils::execute($sql); 
		        
		        $userRow = $result->fetch_assoc();
				$this->userId = $userRow['user_id'];
	
			    //add host to the list of attendee's
		        $eventUser = new User( $this->userId );
		        $attendeeObject['hash'] = hash('sha512', $eventUser->getPhoneNumber());
				$attendeeObject['status'] = 1; //host is always attending
				
				if($eventUser->id != null)
				{
					$attendeeObject['name'] = $eventUser->name;					
				}
				else
				{
					$attendeeObject['name'] = 'Event Host';
				}
				$this->attendees[] = $attendeeObject;
			}
			else
			{
				throw new Exception('Event not found.');
			}			
			
		}
		else
		{
			throw new Exception('Event id not set.');
		}
	}
} 

?>