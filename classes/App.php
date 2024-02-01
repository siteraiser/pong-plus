<?php

class App {  
	public $pdo;
	function __construct() {
	
	//add pdo 
		include($_SERVER["DOCUMENT_ROOT"].'/system/dbconn.php');		
		
		if(!is_object($this->pdo)){
			try {
				$this->pdo = new PDO('mysql:host=localhost;dbname='.$database, $username, $password);    					
			} catch (PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
				die();
			} 				
		}
	}
	
	
	public function loadModel($path) {
		$var=explode('/',$path);
		$name =	end($var);
		$loadname = 'models/'.$path.'.php';
		if(file_exists($loadname) AND !isset($this->$name)){
			include_once($loadname);
			$this->$name = new $name;				
		}
	}
	
	
	
}