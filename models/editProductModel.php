<?php 
class editProductModel extends App {  
	function toggleIAddr($id,$status){	
		
		$query='UPDATE i_addresses SET 
			status=:status
			WHERE id=:id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':status'=>$status,
			':id'=>$id));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}


	//also in initialize...
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


	function sameIntegratedAddress(){
		
		$stmt=$this->pdo->prepare(
		"SELECT * FROM i_addresses 	
		WHERE i_addresses.product_id = ? AND i_addresses.comment = ? AND i_addresses.ask_amount = ? AND i_addresses.port = ?" );// AND i_addresses.status = '1'
		$stmt->execute([$_POST['pid'],$_POST['comment'],$_POST['ask_amount'],$_POST['port']]);		
		if($stmt->rowCount()==0){
			return false;
		}	
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row['iaddr'];
	}


	//also in addproduct
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
		WHERE iaddr = ? AND i_addresses.status = '1'");
		$stmt->execute([$iaddr]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return ["comment"=>$row['comment'],"ask_amount"=>$row['ask_amount'],"port"=>$row['port']];
	}

	function integratedAddressExistsElsewhere($iaddr){

		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses WHERE iaddr = ? AND status = '1' AND NOT(id = ?) AND NOT(product_id = ?)");
		$stmt->execute([$iaddr['iaddr'],$iaddr['id'],$iaddr['product_id']]);		
		
		if($stmt->rowCount()==0){
			return false;
		}
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}


	function portExists($port,$ask_amount){

		$stmt=$this->pdo->prepare(
		"SELECT * FROM i_addresses 	
		INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE i_addresses.port = ? AND i_addresses.status = '1' AND i_addresses.ask_amount = ?");
		$stmt->execute([$port,$ask_amount]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return ["comment"=>$row['comment'],"iaddr"=>$row['iaddr']];
	}

	function updateProduct(){

		$query='UPDATE products SET 
			comment=:comment,
			out_message=:out_message,
			out_message_uuid=:out_message_uuid,
			ask_amount=:ask_amount,
			respond_amount=:respond_amount,
			port=:port,
			status=:status
			WHERE id=:id';
		
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':comment'=>$_POST['comment'],	
			':out_message'=>$_POST['out_message'],
			':out_message_uuid'=>isset($_POST['out_message_uuid']) ? 1 : 0,
			':ask_amount'=>$_POST['ask_amount'],
			':respond_amount'=>($_POST['respond_amount']==''?0:$_POST['respond_amount']),
			':port'=>$_POST['port'],
			':status'=>isset($_POST['status']) ? 1 : 0,
			':id'=>$_POST['pid']));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return $_POST['pid'];
	}
}