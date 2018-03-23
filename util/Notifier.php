<?php

class Notifier 
{
	public static function sendText($phoneNumber , $message)
	{		
	
		$from = '98975';
		
		$message = rawurlencode($message);
		
		$phoneNumber = '1' . $phoneNumber;
		
		$url = sprintf("https://rest.nexmo.com/sc/us/alert/json?api_key=ffa2db4f&api_secret=aa7f4328&from=%d&to=%d&messagebody=%s",$from, $phoneNumber, $message);
				
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
							
		curl_close($ch);
	}
	
	public static function sendVerificationText($phoneNumber , $code)
	{		
	
		$from = '31089';
				
		$phoneNumber = '1' . $phoneNumber;
		
		$url = sprintf("https://rest.nexmo.com/sc/us/2fa/json?api_key=ffa2db4f&api_secret=aa7f4328&from=%d&to=%d&pin=%s",$from, $phoneNumber, $code);
								
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
							
		curl_close($ch);
	}
	
	public static function sendPushNotification($deviceToken, $message, $custom=NULL, $aps=NULL)
	{		
		// Put your private key's passphrase here:
		$passphrase = 'sweat29?roes';
		
		////////////////////////////////////////////////////////////////////////////////
		
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', '/var/www/eventy/util/ck.pem');
		stream_context_set_option($ctx, 'ssl', 'cafile', 'entrust_2048_ca.cer');
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		
		// Open a connection to the APNS server
		$fp = stream_socket_client(
			'ssl://gateway.push.apple.com:2195', $err,
			$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		
		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
		
		error_log( "Sending Push notification to " . $deviceToken );
		error_log('Connected to APNS');
		
		// Create the payload body
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default'
			);
			
		if($custom != NULL)
		{
			foreach($custom as $key => $value)
				$body[$key] = $value;
		}
		
		if($aps != NULL)
		{
			foreach($aps as $key => $value)
				$body['aps'][$key] = $value;
		}
			
		
		// Encode the payload as JSON
		$payload = json_encode($body);
		
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
				
		
		if (!$result)
			error_log('Message not delivered');
		else
			error_log('Message successfully delivered');
		
		
		// Close the connection to the server
		fclose($fp);
	}
}
?>