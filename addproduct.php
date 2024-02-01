<?php 
require_once('system/startup.php');
$Product = new Product;
header('Content-type: application/json');
echo json_encode($Product->add());	
