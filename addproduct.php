<?php /*
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");
*/
//$data = json_decode(file_get_contents('php://input'), true);

require_once('dbconn.php');

require_once('walletapi.php');

//also in initialize...
function getProductsList($pdo, $product_id){
	$stmt=$pdo->prepare("SELECT * FROM products WHERE id = ?");
	$stmt->execute(array($product_id));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $rows;
}

function getIAddresses($pdo,$product_id){
	$stmt=$pdo->prepare("SELECT * FROM i_addresses WHERE product_id = ?");
	$stmt->execute(array($product_id));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $rows;
}




function insertIntegrateAddress($pdo,$product_id,$iaddr){

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



function integratedAddressExists($pdo,$iaddr){

	$stmt=$pdo->prepare("SELECT * FROM i_addresses INNER JOIN products ON i_addresses.product_id = products.id 
	WHERE iaddr = ? ");//AND i_addresses.status = '1'
	$stmt->execute([$iaddr]);		
	if($stmt->rowCount()==0){
		return false;
	}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	
	return $row['comment'];
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

function insertProduct($pdo){

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
			
	$stmt=$pdo->prepare($query);
	$stmt->execute($array);		
	if($stmt->rowCount()==0){
		return false;
	}
	return $pdo->lastInsertId('id');
}

$errors=[];

if(!empty($_POST)){
	if($_POST['comment']=='' || $_POST['ask_amount'] ==  '' || $_POST['port'] ==  '' ){		
		$errors[] = "Required fields missing";
	}
	//Generate integrated address
	if(empty($errors)){	
		$export_address_result = json_decode(export_iaddress($ip,$port,$user,$pass,$_POST['port'],$_POST['comment'],$_POST['ask_amount']));
		if($export_address_result ==''){			
			$errors[] = "Couldn't generate integrated address";
		}
	}
	//See if integrated address exists
	if(empty($errors)){
		$comment = integratedAddressExists($pdo,$export_address_result->result->integrated_address);
		if($comment !== false){
			$errors[] = "Integrated address already exists for \"$comment\". Change comment, ask amount or port.";
		}
	}
	//Check if port is being used with same price
	if(empty($errors)){
		$result = portExists($pdo,$_POST['port'],$_POST['ask_amount']);
		if($result !== false){
		$errors[] = "Port already exists for \"{$result['comment']}\" with ask amount {$_POST['ask_amount']} and active integrated address: ".$result['iaddr'];
		}
	}
	//Save Product
	if(empty($errors)){			
		$product_id = insertProduct($pdo);
		if($product_id === false){
			$errors[] = "Failed to add product to db";
		}	
	}

	//Save integrated address with product id
	if(empty($errors)){		
		$ia_id = insertIntegrateAddress($pdo,$product_id,$export_address_result->result->integrated_address);
		if($ia_id  === false){
			$errors[] = "Failed to add integrated address to db";
		}
	}
	
}

if(empty($errors)){
	$product_results = getProductsList($pdo,$product_id);
	foreach ($product_results as &$product){
		$product['iaddress'] = getIAddresses($pdo,$product['id']);
		
	}

	$response = ["success"=>true,"products"=>$product_results];
}else{	
	$response = ["success"=>false,"errors"=>$errors];
}



header('Content-type: application/json');
echo json_encode($response);
