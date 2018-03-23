<?php
require_once("../util/DBUtils.php");

class User 
{
	public $id;
	public $name; 
	
	// -- CHANGE ALL THESE FIELDS BELOW TO PRIVATE
	private $password;
	public $email; 
	private $phoneNumber;
	public $isAppInstalled;
	public $location; // recent location of the user - array containing (latitude & longitude)
	private $deviceToken;
	
	public function __construct($userId=0) 
	{ 
		if( $userId == 0 )
		{
			$name = "Empty User";
		}
		else
		{
			$this->id = intval( $userId );
			
			try
			{
				$this->loadUserFromDB();
			}
			catch(Exception $e)
			{
				throw new Exception('User not found.');
			}
		}
	} 
	
	public function setPassword( $password ) 
	{
		//hash on the client side and server side
		$this->password = hash('sha512', $password);
	}
	
	public function getPassword() 
	{
		return $this->password;
	}
	
	public function setDeviceToken( $deviceToken )
	{
		$this->deviceToken = $deviceToken;
	}
	
	public function getDeviceToken()
	{
		return $this->deviceToken;
	}

	public function getPhoneNumber() 
	{
		return $this->phoneNumber;
	}
	
	public function setPhoneNumber( $phoneNumber ) 
	{
		if( strlen($phoneNumber) == 10  || $phoneNumber == '0')
		{
			$this->phoneNumber = $phoneNumber; 
		}
		else
		{
			throw new Exception('Phone number is not 10 digits long. Please don\'t include country codes.');
		}
	}
	
	public function unsetPhoneNumber()
	{
		unset( $this->phoneNumber );
	}
	
	public function save() 
	{ 
		$db = new DB('eventy');

		$tableName = 'users';
		$params = array();
		$params[$tableName . '.name'] = $db->getSQLValueString($this->name,'text');
		$params[$tableName . '.password'] = $db->getSQLValueString($this->password,'text');
		$params[$tableName . '.email'] = $db->getSQLValueString($this->email,'text');
				
		if( isset($this->phoneNumber) )
		{
			$params[$tableName . '.phone_number'] = $db->getSQLValueString($this->phoneNumber,'text');
		}

		if( isset($this->location) )
		{
			$params[$tableName . '.location_lat'] = $db->getSQLValueString($this->location['latitude'],'text');
			$params[$tableName . '.location_long'] = $db->getSQLValueString($this->location['longitude'],'int');
		}

		if( isset($this->id) )
		{
			$params[$tableName . '.id'] = $this->id;
		}
		
		if( isset($this->deviceToken) )
		{
			$params[$tableName . '.app_installed'] = 1;

			$params[$tableName . '.device_token'] = $db->getSQLValueString($this->deviceToken,'text');
		}
			
		$update = array();
		
		foreach ($params as $key => $value) 
		{
			$key = str_replace($tableName . '.', '', $key);
			$update[] = $key . '=' . $value;
		}	
		
		
		
		$sql = sprintf("INSERT INTO " . $tableName . " (%s) VALUES (%s)",implode(',',array_keys($params)) , implode(',',array_values($params)) );
		
		if( !isset($this->phoneNumber) )
		{
			$sql .= sprintf(" ON DUPLICATE KEY UPDATE %s;" , implode(',',$update));
		}
		else
		{
			$selectsql = sprintf("SELECT app_installed FROM " . $tableName . " WHERE phone_number=%s", $db->getSQLValueString($this->phoneNumber,'text') );
			
			$result = $db->query( $selectsql );
			
			if( $result->num_rows > 0 )
			{
				$userRow = $result->fetch_assoc();
				
				if( intval($userRow['app_installed']) ==  0 )
				{
					$sql .= sprintf(" ON DUPLICATE KEY UPDATE %s;" , implode(',',$update));
				}
				else
				{
					throw new Exception('There is already a user with that phone number.');
				}
			}

			
		}
							
		error_log($sql);					
							
		$db->query( $sql );
								
		if( !isset($this->id) && $db->insert_id != 0 )	
		{
			$this->id = $db->insert_id;
		}											
				
		$db->close();
		
		
	} 
	
	public function attemptLogin() 
	{ 
		$db = new DB('eventy');

		$tableName = 'users';
		
		$sql = sprintf("SELECT users.id FROM " . $tableName . " WHERE users.email=%s AND users.password=%s;" , 
			$db->getSQLValueString($this->email,'text'), $db->getSQLValueString($this->password,'text'));
														
		$result = DBUtils::execute($sql); 
		
		if($result->num_rows > 0)
		{
			$userRow = $result->fetch_assoc();

			$this->id = intval($userRow['id']);
			$this->loadUserFromDB();
		}
		else
		{
			throw new Exception('No user with that email and password.');
		}
												
		$db->close();
	}
	
	// Table row to object converter
	private function loadUserFromDB()
	{
		if( isset($this->id) )
		{
			$sql = sprintf('SELECT users.* FROM users WHERE users.id = %d;', $this->id);
												
			$result = DBUtils::execute($sql); 
			
			if($result->num_rows > 0)
			{
				$userRow = $result->fetch_assoc();
			
				$this->name 			= $userRow['name'];			
				$this->password			= $userRow['password'];
				$this->email			= $userRow['email'];
				$this->location 		= array('latitude'=>$userRow['location_lat'] , 'longitude'=>$userRow['location_long']);
				$this->phoneNumber		= $userRow['phone_number'];
				$this->isAppInstalled	= $userRow['app_installed'];
				$this->deviceToken		= $userRow['device_token'];
			}
			else
			{
				throw new Exception('User not found.');
			}			
			
		}
		else
		{
			throw new Exception('User id not set.');
		}
	}
	
} 

?>