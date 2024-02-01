<?php 

//require_once('/system/dbconn.php');

function class_loader($class){
require('classes/' . $class . '.php');//---- might be a badly named model if gives error here   
}
spl_autoload_register('class_loader');

//$App = new App;
