Hydna PHP Client Library
This first version of our client library for PHP implements support for the Hydna Push API. Future versions will include support for the full set of features.

More info: https://www.hydna.com/

# Hydna PHP Client Library

This first version of our client library for PHP implements support for the
Hydna Push API. Future versions will include support for the full set of
features.

More info: https://www.hydna.com/

## Usage

The `hydna`-package exposes two functions:

    require("hydna-push.php");

    $hydna = new Hydna(); 

	try{
		// send a message
		$hydna->push("http://public.hydna.net/1122556828", "Hello World from PHP");
		
		// emit a signal
		$hydna->emit("http://public.hydna.net/1122556828", "Hello World signal from PHP");
		
	}catch(Exception $e){
		print $e->getMessage();
	}    