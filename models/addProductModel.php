<?php 
class addProductModel extends App {  
	
	function getProductsList($product_id){
		//also in initialize...
		$stmt=$this->pdo->prepare("SELECT * FROM products WHERE id = ?");
		$stmt->execute(array($product_id));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}

	function getIAddresses($product_id){
		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses WHERE product_id = ?");
		$stmt->execute(array($product_id));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}

	function insertIntegratedAddress($product_id,$iaddr){

		$query='INSERT INTO i_addresses (
			iaddr,
			ask_amount,
			comment,
			port,
			product_id,
			status
			)
			VALUES
			(?,?,?,?,?,?)';	
		
		$array=array(
			$iaddr,
			$_POST['ask_amount'],
			$_POST['comment'],
			$_POST['port'],
			$product_id,
			1
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $this->pdo->lastInsertId('id');
	}



	function integratedAddressExists($iaddr){

		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE iaddr = ? ");//AND i_addresses.status = '1'
		$stmt->execute([$iaddr]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return $row['comment'];
	}


	function portExists($port,$ask_amount){

		$stmt=$this->pdo->prepare(
		"SELECT * FROM i_addresses 	
		INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE i_addresses.port = ? AND i_addresses.ask_amount = ?");
		$stmt->execute([$port,$ask_amount]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return ["comment"=>$row['comment'],"iaddr"=>$row['iaddr']];
	}

	function insertProduct(){

		$query='INSERT INTO products (
			comment,
			out_message,
			out_message_uuid,
			ask_amount,
			respond_amount,
			port,
			status
			)
			VALUES
			(?,?,?,?,?,?,?)';	
		
		$array=array(
			$_POST['comment'],
			$_POST['out_message'],
			isset($_POST['out_message_uuid']) ? 1 : 0,
			$_POST['ask_amount'],
			($_POST['respond_amount']==''?0:$_POST['respond_amount']),
			$_POST['port'],
			1
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $this->pdo->lastInsertId('id');
	}
}