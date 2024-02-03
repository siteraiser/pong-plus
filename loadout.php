<?php 
require_once('system/startup.php');
$Loadout = new Loadout;
header('Content-type: application/json');
echo json_encode($Loadout->load());	
