<?php

class HydnaUtil{
	
	const MAX_PAYLOAD_SIZE		= 0xFFF8;
	const MAX_TOKEN_SIZE		= 0xFFFF;
	const DEFAULT_PORT			= 80;
	
	public static function clean_payload($data){
		
		if(empty($data)){
			throw new Exception("Payload expected");
		}
		
		if(mb_strlen($data, "UTF-8") > self::MAX_PAYLOAD_SIZE){
			throw new Exception("Payload exceeds maximum length allowed");
		}
		
		return $data;
	}
	
	public static function clean_token($token){
		
		if(mb_strlen($token, "UTF-8") > self::MAX_TOKEN_SIZE){
			throw new Exception("Token exceeds maximum length allowed");
		}
		
		return $token;
	}
	
	public static function clean_prio($prio){
		
		if(!is_numeric($prio)){
			throw new Exception("Priority needs to be a number 0-3");
		}
		
		if($prio > 3 | $prio < 0){
			throw new Exception("Priority needs to be 0-3");
		}
		
		return $prio;
	}
	
	public static function parse_uri($uri){
		
		if(strpos($uri, "http" ) === false){
			$uri = sprintf("http://%s",$uri);
		}
		
		$components = HydnaUtil::get_url_parts($uri);
		
		if(empty($components['scheme'])){
			throw new Exception("No url scheme found");
		}
		
	    $token = HydnaUtil::clean_token($components['query']);
	
        return array(
            "scheme" => $components['scheme'],
            "host" => $components['host'],
            "channel" => $components['path'],
            "token" => $token,
            "port" => $components['port']
        );
	}
	
	public static function get_url_parts($uri){
		
		$components = parse_url($uri);
		
		if(!array_key_exists("path", $components)){
			$components['path'] = "/";
		}
		
		if(!array_key_exists("query", $components)){
			$components['query'] = "";
		}
		
		if(!array_key_exists("port", $components)){
			$components['port'] = self::DEFAULT_PORT;
		}
		
		return $components;
	}
	
};

class Hydna{
	
	public $agent = "hydna-php-push";
	
	public static $TIMEOUT = 5;
	
	public function push($domain, $data, $prio=0, $ctoken=""){
		
        $headers = array(
            'Content-Type: text/plain',
            sprintf('User-Agent: %s', $this->agent)
        );
		
		$prio = HydnaUtil::clean_prio($prio);
		$headers[] = sprintf('X-Priority: %s', $prio);
		
		return $this->send($domain, $headers, $data);
	}
	
	public function emit($domain, $signal, $ctoken=""){

        $headers = array(
            'X-Emit: yes',
            'Content-Type: text/plain',
            sprintf('User-Agent: %s',$this->agent)
        );
		
		return $this->send($domain, $headers, $signal);
	}
	
	private function send($url, $headers, $data){
		if(!extension_loaded('curl')){
			die('Sorry cURL is not installed!');
		}
		
		$uri = HydnaUtil::parse_uri($url);

		$curl_handle = curl_init();
		
        $conn = sprintf('%s://%s:%d%s', $uri['scheme'], $uri['host'],
            $uri['port'], $uri['channel']);
		
		if(!empty($uri['token'])){
			$conn .= sprintf('?%s', $uri['token']);
		}
		
		curl_setopt($curl_handle, CURLOPT_URL, $conn);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, Hydna::$TIMEOUT);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
		
		$data = HydnaUtil::clean_payload($data);
		
		curl_setopt($curl_handle, CURLOPT_POST, true);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
		
		$result = curl_exec($curl_handle);
		
		$code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		
		curl_close($curl_handle);
		
		if($code != 200){
			throw new Exception("Error, response code: ".$code);
		}
		
		return true;
	}
}

?>
