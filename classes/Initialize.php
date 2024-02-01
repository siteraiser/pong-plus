<?php 
class Initialize extends App {  	
	function getProducts(){
		require_once('system/dbtablesetup.php');
		$this->loadModel('InitializeModel');
		$product_results = $this->InitializeModel->getProductsList();		
		
		foreach ($product_results as &$product){
			$product['iaddress'] = $this->InitializeModel->getIAddresses($product['id']);			
		}		
		return ["products"=>$product_results];		
	}
}