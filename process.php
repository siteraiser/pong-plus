<?php

require_once('dbconn.php');
require_once('walletapi.php');
class UUID {
	//Thank you commenters in the PHP docs
  public static function v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

      // 32 bits for "time_low"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),

      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),

      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,

      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,

      // 48 bits for "node"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}

function getInstalledTime($pdo){

	$stmt=$pdo->prepare("SELECT install_time_utc FROM settings");
	$stmt->execute([]);		
	if($stmt->rowCount()==0){
		return false;
	}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row['install_time_utc'];
}

define('INSTALL_TIME_UTC', (string)getInstalledTime($pdo));

function txExists($pdo,$tx){

	$stmt=$pdo->prepare("SELECT id FROM incoming WHERE txid = ?");
	$stmt->execute([$tx->txid]);		
	if($stmt->rowCount()==0){
		return false;
	}
	//$row = $stmt->fetch(PDO::FETCH_ASSOC);
	//settings install_time_utc
	return true;
}

function insertNewTransaction($pdo,$tx){
	
	
	if(txExists($pdo,$tx)|| $tx->time_utc < INSTALL_TIME_UTC){// 
		return false;
	}
	
	//2024-01-23 22:22:43 in UTC
	$query='INSERT INTO incoming (
		txid,
		buyer_address,
		amount,
		port,
		processed,
		block_height,
		time_utc
		)
		VALUES
		(?,?,?,?,?,?,?)
		';	
	
	$array=array(
		$tx->txid,
		$tx->buyer_address,
		$tx->amount,
		$tx->port,
		0,
		$tx->height,
		$tx->time_utc,
		);				
			
	$stmt=$pdo->prepare($query);
	$stmt->execute($array);		
	if($stmt->rowCount()==0){
		return false;
	}
	return $tx;
}

function makeTxObject($pdo,$entry){
	
	$tx = [];	
	$tx['txid'] = $entry->txid;
	$tx['amount'] = $entry->amount;	
	$tx['height'] = $entry->height;
	
	$given = new DateTime($entry->time);
	$given->setTimezone(new DateTimeZone("UTC"));	
	$tx['time_utc'] = $given->format("Y-m-d H:i:s");
					
	//Find buyer address in payload
	foreach($entry->payload_rpc as $payload){
		if($payload->name == "R" && $payload->datatype == "A"){
			$tx['buyer_address'] = $payload->value;
		}else if($payload->name == "D" && $payload->datatype == "U"){
			$tx['port'] = $payload->value;					
		}					
	}
	
	return (object)$tx;
}


//check responses to ensure they went through, if not mark as not processed 
function unConfirmedTxs($pdo){

	$stmt=$pdo->prepare("SELECT * FROM responses WHERE confirmed = '0'");
	$stmt->execute([]);		
	if($stmt->rowCount()==0){
		return [];
	}
	
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function removeResponse($pdo,$txid){

	$stmt=$pdo->prepare("DELETE FROM responses WHERE txid = ?");
	$stmt->execute([$txid]);		
	if($stmt->rowCount()==0){
		return [];
	}
	
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markResAsConfirmed($pdo,$txid){

	$query='UPDATE responses SET 
		confirmed=:confirmed
		WHERE txid=:txid';	
	
	$stmt=$pdo->prepare($query);
	$stmt->execute(array(
		':confirmed'=>1,
		':txid'=>$txid));				
				
	if($stmt->rowCount()==0){
		return false;
	}
}
function markIncAsNotProcessed($pdo,$id){

	$query='UPDATE incoming SET 
		processed=:processed
		WHERE id=:id';	
	
	$stmt=$pdo->prepare($query);
	$stmt->execute(array(
		':processed'=>0,
		':id'=>$id));				
				
	if($stmt->rowCount()==0){
		return false;
	}
}
$messages = [];
$unConfirmed = unConfirmedTxs($pdo);
//go through the resposes that haven't been confirmed.
foreach($unConfirmed as $out_message){
	//make sure the response is at least one block old before checking.
	$given = new DateTime();
	$given->setTimezone(new DateTimeZone("UTC"));	
	$given->modify('-18 seconds');
	$time_utc = $given->format("Y-m-d H:i:s");
	
	if($out_message['time_utc'] < $time_utc){
		$check_transaction_result = check_transaction($ip,$port,$user,$pass,$out_message['txid']);
		$check_transaction_result = json_decode($check_transaction_result);

		//succesfully confirmed 
		if(!isset($check_transaction_result->errors) && isset($check_transaction_result->result)){		
			markResAsConfirmed($pdo,$out_message['txid']);	
			$messages[] = $out_message['type']." confirmed with txid:".$out_message['txid'];	
			//send post message to your web api here... 
			if($out_message['out_message_uuid'] == 1){
				//$customAPIAddress = $out_message['out_message'];	
				
			}
			
		}else{
			//delete the response reccord and set the incoming to not processed.
			removeResponse($pdo,$out_message['txid']);
			markIncAsNotProcessed($pdo,$out_message['incoming_id']);		
		}
	}
}




$errors = [];
//$notProcessed=[];
//Get transfers and save them if they are new and later than the db creation time.	
$export_transfers_result =	export_transfers($ip,$port,$user,$pass);
$export_transfers_result = json_decode($export_transfers_result);
if($export_transfers_result === NULL){
	$errors[] = "Wallet Connection Error.";
}else{

	foreach($export_transfers_result->result->entries as $entry){		
		//See if there is a payload
		if(isset($entry->payload_rpc)){
			$tx = makeTxObject($pdo,$entry);
			insertNewTransaction($pdo,$tx);
		}
	}
}	
	

function unprocessedTxs($pdo){

	$stmt=$pdo->prepare("SELECT * FROM incoming WHERE processed = '0'");
	$stmt->execute([]);		
	if($stmt->rowCount()==0){
		return false;
	}
	
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/*	*/

	
function getIAsettings($pdo,$tx){

	$stmt=$pdo->prepare("
	SELECT * FROM i_addresses 
	INNER JOIN products ON i_addresses.product_id = products.id  WHERE i_addresses.ask_amount = ? AND i_addresses.port = ? AND i_addresses.status = '1'");
	$stmt->execute([$tx['amount'],$tx['port']]);		
	if($stmt->rowCount()==0){
		return false;
	}
	
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

function markAsProcessed($pdo,$txid){

	$query='UPDATE incoming SET 
		processed=:processed
		WHERE txid=:txid';	
	
	$stmt=$pdo->prepare($query);
	$stmt->execute(array(
		':processed'=>1,
		':txid'=>$txid));				
				
	if($stmt->rowCount()==0){
		return false;
	}
}

function insertResponse($pdo,$response){

	$query='INSERT INTO responses (
		incoming_id,
		txid,
		type,
		buyer_address,
		out_amount,
		port,
		out_message,
		out_message_uuid,
		time_utc
		)
		VALUES
		(?,?,?,?,?,?,?,?,?)
		';	
	
	$array=array(
		$response->incoming_id,
		$response->txid,
		$response->type,
		$response->buyer_address,
		$response->out_amount,
		$response->port,
		$response->out_message,
		$response->out_message_uuid,
		$response->time_utc
		);				
			
	$stmt=$pdo->prepare($query);
	$stmt->execute($array);		
	if($stmt->rowCount()==0){
		return false;
	}
	return true;
}




$notProcessed=[];
$new=unprocessedTxs($pdo);
if($new !== false){
	$notProcessed = $new;
}


$type = '';	
$UUID = new UUID;	
foreach($notProcessed as $tx){
	
	$settings = getIAsettings($pdo,$tx);

	if($settings !== false){
		$respond_amount = $settings['respond_amount'];
		$address = $tx['buyer_address'];
		
		if($settings['out_message_uuid'] == 1){
			//$customAPIAddress = $settings['out_message'];			
			$settings['out_message'] = $UUID->v4();
			//No longer safe to assume the transaction went through... 
			//send a curl request to endpoint on line: 190
			//$customAPIAddress
		}
		
		$out_message = $settings['out_message'];
		$responseTXID = '';
		//Send Response to buyer
		$payload_result = payload($ip, $port, $user, $pass, $respond_amount, $address, $scid, $out_message);
		$payload_result = json_decode($payload_result);
		
		//$payload_result ='';
			
		//Ensure that the response transfer is successful
		if($payload_result != null && $payload_result->result){
			$type="sale";
			$responseTXID = $payload_result->result->txid;	

			$messages[] = "Responding to Transaction for \"{$settings['comment']}\",  Ask Amount:{$tx['amount']}, Port:{$tx['port']} with Out Message:\"{$settings['out_message']}\" and Respond Amount:{$settings['respond_amount']} txid:({$tx['txid']}) response txid:($responseTXID)";
		}else{
			$errors[] = "Error Processing Transaction {$tx['txid']}";
		}
		
	}else{
		//No mathcing products / I. Addresses found
		//Send Refund to buyer
		$respond_amount = $tx['amount'];
		$address = $tx['buyer_address'];
		$out_message = "Integrated Address Inactive.";	
		
		$payload_result = payload($ip, $port, $user, $pass, $respond_amount, $address, $scid, $out_message);
		$payload_result = json_decode($payload_result);
		
		//$payload_result ='';
					
		$responseTXID = '';
		//Ensure that the response transfer is successful
		if($payload_result != null && $payload_result->result){
			$type="refund";
			$responseTXID = $payload_result->result->txid;
			

			$messages[] = "Initiating Refund for Ask Amount:{$tx['amount']} and Port:{$tx['port']} to $address!";
		}else{
			$errors[] = "Error Refunding Transaction {$tx['txid']}";
		}
	} 
	if(empty($errors)){
		$result = markAsProcessed($pdo,$tx['txid']);
		
		$given = new DateTime();
		$given->setTimezone(new DateTimeZone("UTC"));	
		$time_utc = $given->format("Y-m-d H:i:s");
		//could save time of next block instead of waiting 18 seconds for a confirmation check turbo mode
		if($result !== false){
			$response = (object)[
			"incoming_id"=>$tx['id'],
			"txid"=>$responseTXID,
			"type"=>$type,
			"buyer_address"=>$address,
			"out_amount"=>$respond_amount,
			"port"=>$tx['port'],
			"out_message"=>$out_message,
			"out_message_uuid"=>($settings !==false?$settings['out_message_uuid']:''),
			"time_utc"=>$time_utc
			];
			
			
			insertResponse($pdo,$response);
		}
	}
	
		
}	


if(empty($errors)){
	$response = ["success"=>true,"messages"=>$messages];
}else{	
	$response = ["success"=>false,"errors"=>$errors];
}



header('Content-type: application/json');
echo json_encode($response);
