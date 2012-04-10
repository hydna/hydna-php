<?php

require("hydna-push.php");

$hydna = new Hydna(); 

try{
	$hydna->push("http://public.hydna.net/4000", "Hello World from PHP");
}catch(Exception $e){
	print $e->getMessage();
}

?>