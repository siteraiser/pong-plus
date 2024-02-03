<?php 
class Loadout extends App {  	
	function load(){

		$this->loadModel('loadoutModel');
		$transactions = $this->loadoutModel->getTransactionList();		
				
		return ["transactions"=>$transactions];		
	}
}
