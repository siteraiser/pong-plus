<?php 

$data = json_decode(file_get_contents('php://input'), true);

require_once('dbconn.php');
require_once('dbtablesetup.php');
	


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


$product_results = getProductsList($pdo);

foreach ($product_results as &$product){
	$product['iaddress'] = getIAddresses($pdo,$product['id']);
	
}



$response = ["products"=>$product_results];
header('Content-type: application/json');
echo json_encode($response);
