<?php 
class processModel extends App {  
	public $installed_time_utc='';

	function setInstalledTime(){

		$stmt=$this->pdo->prepare("SELECT value FROM settings WHERE name = 'install_time_utc'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);		
		$this->installed_time_utc = $row['value'];
	}



	function txExists($tx){

		$stmt=$this->pdo->prepare("SELECT id FROM incoming WHERE txid = ?");
		$stmt->execute([$tx->txid]);		
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}

	function insertNewTransaction($tx){
		
		if($this->txExists($tx) || $tx->time_utc < $this->installed_time_utc){// INSTALL_TIME_UTC|| $this->installed_time_utc ==''
			return false;
		}
		
		//2024-01-23 22:22:43 in UTC
		$query='INSERT INTO incoming (
			txid,
			buyer_address,
			amount,
			port,
			for_product_id,
			product_label,
			processed,
			block_height,
			time_utc
			)
			VALUES
			(?,?,?,?,?,?,?,?,?)
			';	
		
		$array=array(
			$tx->txid,
			$tx->buyer_address,
			$tx->amount,
			$tx->port,
			$tx->for_product_id,
			$tx->product_label,
			0,
			$tx->height,
			$tx->time_utc,
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $tx;
	}

	function makeTxObject($entry){
		
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
		
		//Determine product id and current label
		$ia_settings = $this->getIAsettings($tx);
		if($ia_settings!==false){
			$tx['for_product_id'] = $ia_settings['product_id'];
			$tx['product_label'] = $ia_settings['label'];
		}
		
		return (object)$tx;
	}


	//check responses to ensure they went through, if not mark as not processed 
	function unConfirmedTxs(){

		$stmt=$this->pdo->prepare("SELECT DISTINCT txid,time_utc FROM responses WHERE confirmed = '0'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	function removeResponse($txid){

		$stmt=$this->pdo->prepare("DELETE FROM responses WHERE txid = ?");
		$stmt->execute([$txid]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	function markResAsConfirmed($txid){

		$query='UPDATE responses SET 
			confirmed=:confirmed
			WHERE txid=:txid';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':confirmed'=>1,
			':txid'=>$txid));				
					
		if($stmt->rowCount()==0){
			return false;
		}
	}


	function getTXCollection($txid){

		$stmt=$this->pdo->prepare("SELECT incoming_id FROM responses WHERE txid = ?");
		$stmt->execute([$txid]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	function markIncAsNotProcessed($txid){
		$incoming_ids = $this->getTXCollection($txid);
		$ids =[];
		foreach($incoming_ids as $incoming_ids){
			$ids[] = $incoming_ids['incoming_id'];
		}
		
		$ids = implode(",",$ids);
		$result =$this->pdo->query("UPDATE incoming SET processed = '0' WHERE id IN($ids)");
		if($result !== false && $result->rowCount() > 0){		
			return true;
		}else{	
			return false;
		}
	}

	function getConfirmedInc($txid){

		$stmt=$this->pdo->prepare("
		SELECT *, responses.out_message AS response_out_message FROM responses 
		INNER JOIN incoming ON responses.incoming_id = incoming.id 
		RIGHT JOIN i_addresses ON incoming.amount = i_addresses.ask_amount 
		RIGHT JOIN products ON i_addresses.product_id = products.id 
		WHERE incoming.amount = i_addresses.ask_amount AND incoming.port = i_addresses.port AND responses.txid = ?
		");
		$stmt->execute([$txid]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}



	function unprocessedTxs(){

		$stmt=$this->pdo->prepare("SELECT * FROM incoming WHERE processed = '0'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

		
	function getIAsettings($tx){

		$stmt=$this->pdo->prepare("
		SELECT *, i_addresses.comment AS ia_comment FROM i_addresses 
		INNER JOIN products ON i_addresses.product_id = products.id  WHERE i_addresses.ask_amount = ? AND i_addresses.port = ? AND i_addresses.status = '1'");
		$stmt->execute([$tx['amount'],$tx['port']]);		
		if($stmt->rowCount()==0){
			return false;
		}
		
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	function markAsProcessed($txid){

		$query='UPDATE incoming SET 
			processed=:processed
			WHERE txid=:txid';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':processed'=>1,
			':txid'=>$txid));				
					
		if($stmt->rowCount()==0){
			return false;
		}
	}

	function insertResponse($response){

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
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}

}

