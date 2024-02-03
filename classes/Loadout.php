<?php 
class Loadout extends App {  	
	function load(){
		require_once('system/dbtablesetup.php');
		$this->loadModel('loadoutModel');
		$transactions = $this->loadoutModel->getTransactionList();		
		
	//	foreach ($results as &$tx){
	//		$product['iaddress'] = $this->InitializeModel->getIAddresses($product['id']);			
	//	}		
		return ["transactions"=>$transactions];		
	}
}