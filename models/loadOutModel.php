<?php 
class loadoutModel extends App {  
	function getTransactionList(){
		$stmt=$this->pdo->prepare("
		SELECT i.*, ia.* , res.*, p.*
		FROM incoming as i 
		LEFT JOIN products as p 
		ON (i.for_product_id = p.id)
		LEFT JOIN i_addresses as ia 
		ON (i.amount = ia.ask_amount AND i.port = ia.port AND p.id = ia.product_id)
		INNER JOIN responses as res 
		ON (i.id = res.incoming_id) 
		WHERE res.type = 'sale'
		");
		$stmt->execute(array());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
		
}
