<?php
class Process extends App {  
	
	
	function transactions(){
		$this->loadModel("processModel");
		$this->loadModel("deroApiModel");
		$this->loadModel("webApiModel");
		
		$this->processModel->setInstalledTime();
		
		$messages = [];
		$errors = [];
		$unConfirmed = $this->processModel->unConfirmedTxs();

		$confirmed_txns=[];
		//go through the resposes that haven't been confirmed.
		foreach($unConfirmed as $out_message){
			//make sure the response is at least one block old before checking.
			$given = new DateTime();
			$given->setTimezone(new DateTimeZone("UTC"));	
			$given->modify('-36 seconds');//Ensure that one block has passed...
			$time_utc = $given->format("Y-m-d H:i:s");
			
			if($out_message['time_utc'] < $time_utc){
				$check_transaction_result = $this->deroApiModel->getTransferByTXID($out_message['txid']);
				$check_transaction_result = json_decode($check_transaction_result);

				//succesfully confirmed 
				if(!isset($check_transaction_result->errors) && isset($check_transaction_result->result)){		
					$this->processModel->markResAsConfirmed($out_message['txid']);	
					$confirmed_txns[] = $out_message['txid'];
					
					//$messages[] = $out_message['type']." confirmed with txid:".$out_message['txid'];	
					
					
				}else{
					//set the incoming to not processed and delete the response reccord. 			
					$this->processModel->markIncAsNotProcessed($out_message['txid']);	
					$this->processModel->removeResponse($out_message['txid']);	
				}
			}
		}

		foreach($confirmed_txns as $txid){
			$confirmed_incoming = $this->processModel->getConfirmedInc($txid);

			foreach($confirmed_incoming as $record){
				$messages[] = $record['type']." confirmed with txid:".$record['txid'];	
				
				//send post message to your web api here... 
				if($record['out_message_uuid'] == 1 && $record['type'] == 'sale'){
					
					// uuid is in $record['response_out_message']
					//will have to lookup the product...
					// custom API Address is in  = $record['out_message'];	
						/*echo'<pre>';
						var_dump($record);
						echo'</pre>';
						
						$res = $this->webApiModel->newTX($record);
						if($res === false){
							//save somewhere to try again later...
							$errors[] = 'website communication error';
						}*/ 
				}				
			}
		}






		
		//$notProcessed=[];
		//Get transfers and save them if they are new and later than the db creation time.	
		$export_transfers_result = $this->deroApiModel->getTransfers();
		$export_transfers_result = json_decode($export_transfers_result);
		if($export_transfers_result === NULL){
			$errors[] = "Wallet Connection Error.";
		}else{

			foreach($export_transfers_result->result->entries as $entry){		
				//See if there is a payload
				if(isset($entry->payload_rpc)){
					$tx = $this->processModel->makeTxObject($entry);
					$this->processModel->insertNewTransaction($tx);
				}
			}
		}	
			


		$notProcessed=[];
		$new=$this->processModel->unprocessedTxs();
		if($new !== false){
			$notProcessed = $new;
		}


		$type = '';	


		$transfer_list = [];

		foreach($notProcessed as &$tx){
			
			$settings = $this->processModel->getIAsettings($tx);
			
			
			$transfer=[];
			if($settings !== false){
				//Send Response to buyer
				$transfer['respond_amount'] = $settings['respond_amount'];
				$transfer['address'] = $tx['buyer_address'];	
				
				if($settings['out_message_uuid'] == 1){
					$UUID = new UUID;
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
			$payload_result = $this->deroApiModel->transfer($transfer_list);
			$payload_result = json_decode($payload_result);

			if($payload_result != null && $payload_result->result){
				$responseTXID = $payload_result->result->txid;
			}else{
				$errors[] = "Transfer Error";
			}
		}


		if(empty($errors) && $responseTXID !== ''){
			foreach($notProcessed as $tx){
				
				$result = $this->processModel->markAsProcessed($tx['txid']);
					
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
					
					
					$this->processModel->insertResponse($response);
					
					$messages[] = "{$tx['type']} response initiated". ($tx['type'] == 'sale' ? ' for "'.$tx['ia_comment'].'"' : '') . ".";
					
				}
			}
		}



		if(empty($errors)){
			return ["success"=>true,"messages"=>$messages];
		}else{	
			return ["success"=>false,"errors"=>$errors];
		}
	}
}


