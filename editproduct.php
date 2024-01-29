<?php /*
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");
*/
//$data = json_decode(file_get_contents('php://input'), true);

require_once('dbconn.php');

require_once('walletapi.php');









function toggleIAddr($pdo,$id,$status){
	
	
	
	$query='UPDATE i_addresses SET 
		status=:status
		WHERE id=:id';	
	
	$stmt=$pdo->prepare($query);
	$stmt->execute(array(
		':status'=>$status,
		':id'=>$id));				
				
	if($stmt->rowCount()==0){
		return false;
	}
	return true;
}


//also in initialize...
function getProductsList($pdo){
	$stmt=$pdo->prepare("SELECT * FROM products");
	$stmt->execute(array());
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $rows;
}

function getIAddresses($pdo,$product_id){
	$stmt=$pdo->prepare("SELECT * FROM i_addresses WHERE product_id = ?");
	$stmt->execute(array($product_id));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $rows;
}


function sameIntegratedAddress($pdo){
	
	$stmt=$pdo->prepare(
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
function insertIntegratedAddress($pdo,$product_id,$iaddr){

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
			
	$stmt=$pdo->prepare($query);
	$stmt->execute($array);		
	if($stmt->rowCount()==0){
		return false;
	}
	return $pdo->lastInsertId('id');
}

/*
function updateIntegrateAddress($pdo,$product_id,$iaddr){

	$query='UPDATE i_addresses SET  		
		status=:status
		WHERE id=:id';	
	$stmt=$this->pdo->prepare($query);
	$stmt->execute(array(
		':status'=>isset($_POST['status']) ? 1 : 0,
		':id'=>$_POST['pid']));	



	if($stmt->rowCount()==0){
		return false;
	}
	return true;
}
*/

function integratedAddressExists($pdo,$iaddr){

	$stmt=$pdo->prepare("SELECT * FROM i_addresses INNER JOIN products ON i_addresses.product_id = products.id 
	WHERE iaddr = ? AND i_addresses.status = '1'");
	$stmt->execute([$iaddr]);		
	if($stmt->rowCount()==0){
		return false;
	}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	
	return ["comment"=>$row['comment'],"ask_amount"=>$row['ask_amount'],"port"=>$row['port']];
}

function integratedAddressExistsElsewhere($pdo,$iaddr){

	$stmt=$pdo->prepare("SELECT * FROM i_addresses WHERE iaddr = ? AND status = '1' AND NOT(id = ?) AND NOT(product_id = ?)");
	$stmt->execute([$iaddr['iaddr'],$iaddr['id'],$iaddr['product_id']]);		
	
	if($stmt->rowCount()==0){
		return false;
	}
	return $stmt->fetch(PDO::FETCH_ASSOC);
}


function portExists($pdo,$port,$ask_amount){

	$stmt=$pdo->prepare(
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

function updateProduct($pdo){

	$query='UPDATE products SET 
		comment=:comment,
		out_message=:out_message,
		out_message_uuid=:out_message_uuid,
		ask_amount=:ask_amount,
		respond_amount=:respond_amount,
		port=:port,
		status=:status
		WHERE id=:id';
	
	
	$stmt=$pdo->prepare($query);
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

$errors=[];

if(!empty($_POST)){
	
	
	
	
	if($_POST['comment']=='' || $_POST['ask_amount'] ==  '' || $_POST['port'] ==  '' ){		
		$errors[] = "Required fields missing";
	}
	
	
	
	
	$ia = '';
	$same_ia = sameIntegratedAddress($pdo);//no changes to comment, amount or port and is same product id (no need to generate a new one...)
	//Generate integrated address
	if(empty($errors)){	
		
		$export_address_result = json_decode(export_iaddress($ip,$port,$user,$pass,$_POST['port'],$_POST['comment'],$_POST['ask_amount']));
		if($export_address_result ==''){			
			$errors[] = "Couldn't generate integrated address";
		}else{
			$ia = $export_address_result->result->integrated_address;
		}
	}
	

	//See if integrated address exists 
	if(empty($errors) && !$same_ia){
		$result = integratedAddressExists($pdo,$ia);
		//could check if it is disabled and restore it if needed
		if($result !== false){
			$errors[] = "Integrated address already exists for \"{$result['comment']}\". Change comment, ask amount or port.";
		}
	}
	//Check if port is being used with same price
	if(empty($errors) && !$same_ia){
		$result = portExists($pdo,$_POST['port'],$_POST['ask_amount']);
		if($result !== false){
			$errors[] = "Port already exists for \"{$result['comment']}\" with ask amount {$_POST['ask_amount']} and active integrated address: ".$result['iaddr'];
		}
	}
	
	
	//Save Product
	if(empty($errors)){		
		//handle the iaddress status checkboxes
		$changes=false;
		$active = [];
		if(isset($_POST['iaddress_status'])){
			
			foreach($_POST['iaddress_status'] as $key => $value){
				$active[] = $key;		
			}		
		}	
			
		$iadds = getIAddresses($pdo,$_POST['pid']);

		foreach($iadds as $iaddr){
			if(in_array($iaddr['id'],$active)){
				//don't allow active ia for more
				 if($iaddr['status'] == 0){
					$res = integratedAddressExistsElsewhere($pdo,$iaddr);
					if($res !== false){
						$errors[] = "Integrated address already exists for \"{$res['comment']}\", and can only be used for one product at a time with ask amount {$res['ask_amount']} and active integrated address: ".$res['iaddr'];
					}else{
						$changes = toggleIAddr($pdo,$iaddr['id'],1);
					}
				}
			}else{//not submitted as active but is then deactivate
				if($iaddr['status'] == 1){
					$changes = toggleIAddr($pdo,$iaddr['id'],0);
				}
			}
		}	
		$product_id = updateProduct($pdo);
		if($product_id === false && !$changes){
			$errors[] = "Failed to update product in db";
		}	
	}

	//Save integrated address with product id
	if(empty($errors) && !$same_ia){		
		$ia_id = insertIntegratedAddress($pdo,$product_id,$export_address_result->result->integrated_address);
		if($ia_id  === false){
			$errors[] = "Failed to add integrated address to db";
		}
	}
	
}

if(empty($errors)){
	$product_results = getProductsList($pdo);
	foreach ($product_results as &$product){
		$product['iaddress'] = getIAddresses($pdo,$product['id']);		
	}
	$response = ["success"=>true,"products"=>$product_results];
}else{	
	$response = ["success"=>false,"errors"=>$errors];
}



header('Content-type: application/json');
echo json_encode($response);
