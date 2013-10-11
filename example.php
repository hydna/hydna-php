<?php

require("hydna-push.php");

$hydna = new Hydna();

try {
	$hydna->emit("http://public.hydna.net/cli-test", "Hello World from PHP");
} catch(Exception $e) {
	print $e->getMessage()."\n";
}

?>
