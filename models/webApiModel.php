<?php

class webApiModel{  

	public $user="secret";
	public $pass="pass";
	//public $scid="0000000000000000000000000000000000000000000000000000000000000000";

	function connectionErrors($ch){
		// Check HTTP status code
		if (!curl_errno($ch)) {
		  switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
			case 200:  # OK
			  break;
			default:
			  return 'Unexpected HTTP code: '. $http_code ;
		  }
		}else{
			return curl_error($ch) . ' ' . curl_errno($ch);
		}
	}


	//API funtions

	//Gets the list of incoming transfers
	function newTX($tx){

	$data = '{
		"method": "newTX",
		"params": {
			"uuid": "'.$tx['response_out_message'].'"
		}
	}';

	$json = json_decode($data,true);
	$json = json_encode($json);

		$ch = curl_init($tx['out_message']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->pass),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$this->connectionErrors($ch);

		curl_close($ch);

		return $output;

	}
}