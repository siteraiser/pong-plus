<?php 
try {
	$pdo = new PDO('mysql:host=localhost;dbname=pong', 'root', '');    					
} catch (PDOException $e) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
} 
