<?php

class App {  
	public $pdo;
	function __construct() {
	
	//add pdo 
		include_once('/system/dbconn.php');		
		if(!is_object($this->pdo)){
			try {
				$this->pdo = new PDO('mysql:host=localhost;dbname='.$database, $username, $password);    					
			} catch (PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
				die();
			} 				
		}
		$this->pdo = $this->pdo;
	}
	
	public function get_include_contents($filename,$data) {
		foreach($data as $key => $value){
			$$key = $value;
		}
		if (is_file('views/'.$filename.'.php')) {
			ob_start();
	
			include 'views/'.$filename.'.php';
			return ob_get_clean();
		}
		echo $filename . ' is not a valid file!';
		return false;
	}	
	
	public function addView($view,$data) {	
		//sets output in controller's var
		if(!isset($this->output)){$this->output='';}
		$this->output.=$this->get_include_contents($view,$data);		
	}	
	
	public function loadModel($path) {
		$var=explode('/',$path);
		$name =	end($var);
		$loadname = 'models/'.$path.'.php';
		if(file_exists($loadname) AND !isset($this->$name)){
			include_once($loadname);
			$this->$name = new $name;		
/*
			//add pdo 
			include_once('secure/db.inc.php');		
			if(!is_object($this->pdo)){
				try {
					$this->pdo = new PDO('mysql:host=localhost;dbname='.$database, $username, $password);    					
				} catch (PDOException $e) {
					print "Error!: " . $e->getMessage() . "<br/>";
					die();
				} 				
			}
			$this->$name->pdo=$this->pdo;		
			$this->add_props($this->$name);	
*/			
		}
	}
	
	
	
}