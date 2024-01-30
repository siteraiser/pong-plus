<?php 


$ip = "127.0.0.1";//127.0.0.1:10103 (for Engram cyberdeck)
$port="10103";
$user="secret";
$pass="pass";
$scid="0000000000000000000000000000000000000000000000000000000000000000";

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
function check_transaction($ip,$port,$user,$pass,$transaction_id){

$data = '{
    "jsonrpc": "2.0",
    "id": "1",
    "method": "GetTransferbyTXID",
    "params": {
        "txid": "'.$transaction_id.'"
    }
}';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);
	
	echo connectionErrors($ch);

	curl_close($ch);

	return $output;

}













//Creates a new integrated address
//When used as a send to address it will display the in message and fill in the correct amounts as well as allowing a port to be defined 
function export_iaddress($ip,$port,$user,$pass,$d_port,$in_message,$ask_amount){
	$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "MakeIntegratedAddress",
		"params": {
		  "payload_rpc": [
			{
			  "name": "C",
			  "datatype": "S",
			  "value": "'.$in_message.'"
			},
			{
			  "name": "D",
			  "datatype": "U",
			  "value": '.$d_port.'
			},
			{
			  "name": "N",
			  "datatype": "U",
			  "value": 0
			},
			{
			  "name": "V",
			  "datatype": "U",
			  "value": '.$ask_amount.'
			}
		  ]
		}
	}';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);
	
	connectionErrors($ch);
	
	curl_close($ch);

	return $output;

}

//Gets the list of incoming transfers
function export_transfers($ip,$port,$user,$pass){
	$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "GetTransfers",
		"params": {
		  "out": false,
		  "in": true
		}
	}';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);
	
	connectionErrors($ch);

	curl_close($ch);

	return $output;

}

//Creates a transfer to respond to new sales (destination address). 
//If a smart contract is specified it can transfer that. 
//If a respond amount is specified it will send that.
//You have 144 by for an out message (link or uuid etc).
function payload($ip, $port, $user, $pass, $respond_amount, $addr,  $scid, $out_message){	
	
	$data = '{
    "jsonrpc": "2.0",
    "id": "1",
    "method": "transfer",
    "params": {
       "ringsize": 16,
       "transfers":
       [
        {
          "scid": "'.$scid.'",
          "destination": "'.$addr.'",
          "amount": '.$respond_amount.',
          "payload_rpc":
          [
            {
              "name": "C",
              "datatype": "S",
              "value": "'.$out_message.'"
            }
          ]
        }
      ]
    }
  }';

$json = json_decode($data,true);
$json = json_encode($json);

	$ch = curl_init("http://$ip:$port/json_rpc");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
		"Authorization: Basic " . base64_encode($user.':'.$pass),
		"Content-Type: application/json"
	]);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);	
	
	connectionErrors($ch);

	return $output;

}
