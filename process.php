<?php

require_once('dbconn.php');
//require_once('walletapi.php');
require_once($_SERVER["DOCUMENT_ROOT"]."/classes/UUID.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/classes/DeroApi.php");

//Instantiate required classes
$UUID = new UUID;	
$DeroApi = new DeroApi;	

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

	$stmt=$pdo->prepare("SELECT DISTINCT txid,time_utc FROM responses WHERE confirmed = '0'");
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


function getTXCollection($pdo,$txid){

	$stmt=$pdo->prepare("SELECT incoming_id FROM responses WHERE txid = ?");
	$stmt->execute([$txid]);		
	if($stmt->rowCount()==0){
		return [];
	}
	
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markIncAsNotProcessed($pdo,$txid){
	$incoming_ids = getTXCollection($pdo,$txid);
	$ids =[];
	foreach($incoming_ids as $incoming_ids){
		$ids[] = $incoming_ids['incoming_id'];
	}
	
	$ids = implode(",",$ids);
	$result =$pdo->query("UPDATE incoming SET processed = '0' WHERE id IN($ids)");
	if($result !== false && $result->rowCount() > 0){		
		return true;
	}else{	
		return false;
	}
}

function getConfirmedInc($pdo,$txid){

	$stmt=$pdo->prepare("
	SELECT * FROM responses 
	INNER JOIN incoming ON responses.incoming_id = incoming.id 
	WHERE responses.txid = ?");
	$stmt->execute([$txid]);		
	if($stmt->rowCount()==0){
		return [];
	}
	
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



$messages = [];
$unConfirmed = unConfirmedTxs($pdo);



$confirmed_txns=[];
//go through the resposes that haven't been confirmed.
foreach($unConfirmed as $out_message){
	//make sure the response is at least one block old before checking.
	$given = new DateTime();
	$given->setTimezone(new DateTimeZone("UTC"));	
	$given->modify('-18 seconds');
	$time_utc = $given->format("Y-m-d H:i:s");
	
	if($out_message['time_utc'] < $time_utc){
		$check_transaction_result = $DeroApi->getTransferByTXID($out_message['txid']);
		$check_transaction_result = json_decode($check_transaction_result);

		//succesfully confirmed 
		if(!isset($check_transaction_result->errors) && isset($check_transaction_result->result)){		
			markResAsConfirmed($pdo,$out_message['txid']);	
			$confirmed_txns[] = $out_message['txid'];
			
			//$messages[] = $out_message['type']." confirmed with txid:".$out_message['txid'];	
			
			
		}else{
			//set the incoming to not processed and delete the response reccord. 			
			markIncAsNotProcessed($pdo,$out_message['txid']);	
			removeResponse($pdo,$out_message['txid']);	
		}
	}
}

foreach($confirmed_txns as $txid){
	$confirmed_incoming = getConfirmedInc($pdo,$txid);

	foreach($confirmed_incoming as $record){
		$messages[] = $record['type']." confirmed with txid:".$record['txid'];	
		
		//send post message to your web api here... 
		if($record['out_message_uuid'] == 1){
			//$customAPIAddress = $out_message['out_message'];	
				
		}
		
		
	}
}


$errors = [];
//$notProcessed=[];
//Get transfers and save them if they are new and later than the db creation time.	
$export_transfers_result = $DeroApi->getTransfers();
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
	SELECT *, i_addresses.comment AS ia_comment FROM i_addresses 
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


$transfer_list = [];

foreach($notProcessed as &$tx){
	
	$settings = getIAsettings($pdo,$tx);
	
	
	$transfer=[];
	if($settings !== false){
		//Send Response to buyer
		$transfer['respond_amount'] = $settings['respond_amount'];
		$transfer['address'] = $tx['buyer_address'];	
		
		if($settings['out_message_uuid'] == 1){
			$settings['out_message'] = $UUID->v4();
		}
		
		$transfer['out_message'] = $settings['out_message'];
		$transfer['scid'] = "0000000000000000000000000000000000000000000000000000000000000000";
		$transfer_list[]=(object)$transfer;
		//update unprocessed array
		$tx['ia_comment'] = $settings['ia_comment'];
		$tx['respond_amount'] = $transfer['respond_amount'];
		$tx['out_message'] = $transfer['out_message'];
		$tx['type'] = "sale";
		$tx['out_message_uuid'] = $settings['out_message_uuid'];
	}else{
		//No mathcing products / I. Addresses found
		//Send Refund to buyer
		
		$transfer['respond_amount'] = $tx['amount'];
		$transfer['address'] = $tx['buyer_address'];	
		$transfer['out_message'] =  "Integrated Address Inactive.";	
		$transfer['scid'] = "0000000000000000000000000000000000000000000000000000000000000000";
		$transfer_list[]=(object)$transfer;	
		//update unprocessed array
		$tx['respond_amount'] =  $tx['amount'];
		$tx['out_message'] = $transfer['out_message'];
		$tx['type'] = "refund";
		$tx['out_message_uuid'] = '';
	} 
	
}	

unset($tx);



$responseTXID='';
/*die();
*/
if(!empty($transfer_list)){
	$payload_result = $DeroApi->transfer($transfer_list);
	$payload_result = json_decode($payload_result);

	if($payload_result != null && $payload_result->result){
		$responseTXID = $payload_result->result->txid;
	}else{
		$errors[] = "Transfer Error";
	}
}


if(empty($errors) && $responseTXID !== ''){
	foreach($notProcessed as $tx){
		
		$result = markAsProcessed($pdo,$tx['txid']);
			
		$given = new DateTime();
		$given->setTimezone(new DateTimeZone("UTC"));	
		$time_utc = $given->format("Y-m-d H:i:s");
		//could save time of next block instead of waiting 18 seconds for a confirmation check turbo mode
		if($result !== false){
			$response = (object)[
			"incoming_id"=>$tx['id'],
			"txid"=>$responseTXID,
			"type"=>$tx['type'],
			"buyer_address"=>$tx['buyer_address'],
			"out_amount"=>$tx['respond_amount'],
			"port"=>$tx['port'],
			"out_message"=>$tx['out_message'],
			"out_message_uuid"=>$tx['out_message_uuid'],
			"time_utc"=>$time_utc
			];
			
			
			insertResponse($pdo,$response);
			$messages[] = "{$tx['type']} response initiated". ($tx['type'] == 'sale' ? ' for "'.$tx['ia_comment'].'"' : '') . ".";
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
