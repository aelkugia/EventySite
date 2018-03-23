<?php
require_once("../util/DBUtils.php");

require_once("../util/Notifier.php");


class Verification 
{
	public $phoneNumber;
	
	public function __construct($phoneNumber=NULL) 
	{ 
		if( $phoneNumber != NULL )
		{
			if( strlen($phoneNumber) == 10 )
			{
				$this->phoneNumber = $phoneNumber; 
			}
			else
			{
				throw new Exception('Phone number is not 10 digits long. Please don\'t include a country code.');
			}
		}
	} 

	private function generateCode()
	{
		$x = 4; // Amount of digits
		
		$min = pow(10,$x - 1);
		$max = pow(10,$x) - 1;
		
		return rand($min, $max);
	}

	public function createNewVerification() 
	{ 		
		$db = new DB('eventy');

		$tableName = 'verifications';
		
		$params = array();
				
		$params[$tableName . '.phone_number'] = $db->getSQLValueString($this->phoneNumber,'text');
		
		$code = $this->generateCode();
		
		$params[$tableName . '.code'] = $code;
		
		$sql = sprintf("SELECT * FROM verifications WHERE phone_number = %s;", $db->getSQLValueString($this->phoneNumber,'text'));
	
		$result = $db->query( $sql );
		
		if($result->num_rows == 0)
		{
			$sql = sprintf("SELECT users.* FROM users WHERE users.phone_number = %s;", $db->getSQLValueString($this->phoneNumber,'text'));
	
			$result = $db->query( $sql );
			
			if($result->num_rows > 0)
			{
				$userRow = $result->fetch_assoc();
			
				if( intval($userRow['app_installed']) != 0 )
				{
					throw new Exception('A user already exists with that phone number.');
				}
			}

			$sql = sprintf("INSERT INTO " . $tableName . " (%s) VALUES (%s)",implode(',',array_keys($params)) , implode(',',array_values($params)) );

			$db->query( $sql );
		
			Notifier::sendVerificationText($this->phoneNumber , $code );
		}			

	
		$db->close();
	} 
	
	public function attemptVerification($code = NULL) 
	{ 
		if( $code == NULL )
		{
			throw new Exception('Cannot verify without code.');
		}
		
		$db = new DB('eventy');

		$tableName = 'verifications';
		
		$sql = sprintf("SELECT verifications.* FROM " . $tableName . " WHERE verifications.phone_number=%s AND verifications.code=%s;" , 
			$db->getSQLValueString($this->phoneNumber,'text'), $db->getSQLValueString($code,'text'));
														
		$result = DBUtils::execute($sql); 
		
		if($result->num_rows > 0)
		{
			return 'Verification succesful';
		}
		else
		{
			throw new Exception('Verification not valid with code. Verification codes are only valid for 15 minutes.');
		}
												
		$db->close();
	}
	
	public function deleteVerification()
	{
		$db = new DB('eventy');
			
		$sql = sprintf("DELETE FROM verifications WHERE phone_number = %s;" , $db->getSQLValueString($this->phoneNumber,'text'));
														
		$db->query($sql); 
		
		$db->close();
	}	
} 

?>