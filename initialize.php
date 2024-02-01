<?php 
require_once('system/startup.php');
$Initialize = new Initialize;
header('Content-type: application/json');
echo json_encode($Initialize->getProducts());	