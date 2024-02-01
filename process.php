<?php 
require_once('system/startup.php');
$Process = new Process;
header('Content-type: application/json');
echo json_encode($Process->transactions());	