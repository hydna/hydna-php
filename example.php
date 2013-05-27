<?php

require("hydna-push.php");

$hydna = new Hydna();

try{
	$hydna->push("http://public.hydna.net/1", "Hello World from PHP");
}catch(Exception $e){
	print $e->getMessage()."\n";
}

?>
