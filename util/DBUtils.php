<?php
require_once("/var/www/global/DB.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class DBUtils 
{
	//table is the table name 
	//params is a associative array that maps column names to values
	public static function insertTo($table,$params)
	{
		$db = new DB('eventy');
			
		$sql = sprintf("INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",$table , 
			implode(',',array_keys($params)) , implode(',',array_values($params)));

		$result = $db->query( $sql );

		$id = $db->insert_id; 
		
		$db->close();
		
		return $id;
	}

	public static function execute($sql)
	{
		$db = new DB('eventy');
		$result = $db->query( $sql );
		//$db->close();
		return $result;
	}

}
?>