<?php 
class initializeModel extends App {  
	function getProductsList(){
		$stmt=$this->pdo->prepare("SELECT * FROM products");
		$stmt->execute(array());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	function getIAddresses($product_id){
		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses WHERE product_id = ?");
		$stmt->execute(array($product_id));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
}