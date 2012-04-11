<?php

class HydnaUtil{
	
	const MAX_PAYLOAD_SIZE		= 10240;
	const MAX_TOKEN_SIZE		= 0xFFF8;
	const MAX_CHANNEL_VALUE 	= 0xFFFFFFF;
	const DEFAULT_CHANNEL		= 1;
	
	public static function clean_payload($data){
		
		if(empty($data)){
			throw new Exception("Payload excepted");
		}
		
		if(mb_strlen($data, "UTF-8") > self::MAX_PAYLOAD_SIZE){
			throw new Exception("Payload exceeds maximum length allowed");
		}
		
		return $data;
	}
	
	public static function clean_token($token){
		
		if(mb_strlen($token, "utf-8") > self::MAX_TOKEN_SIZE){
			throw new Exception("Token exceeds maximum length allowed");
		}
		
		return $token;
	}
	
	public static function clean_prio($prio){
		
		if(!is_numeric($prio)){
			throw new Exception("Priority needs to be a number between 1-4");
		}
		
		if($prio > 4 | $prio < 1){
			throw new Exception("Priority needs to be 1-4");
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
		
		$channel = HydnaUtil::path_to_channel($components['path']);
	    $token = HydnaUtil::clean_token($components['query']);
	
		return array("scheme" => $components['scheme'], "host" => $components['host'], "channel" => $channel, "token" => $token);
	}
	
	public static function path_to_channel($path){
		
		if(strlen($path) < 2){
			return self::DEFAULT_CHANNEL;  
		}
		
		$parts = explode("/",$path);
		
		if(count($parts) > 3){
			throw new Exception("Unable to parse channel");
		}
		
		if(!is_numeric($parts[1])){
			throw new Exception("Invalid channel"); 
		}
		
		$channel = intval($parts[1]);
		if($channel > self::MAX_CHANNEL_VALUE | $channel == 0){
			throw new Exception("Invalid channel");
		}
		
		return $channel;
	}
	
	public static function get_url_parts($uri){
		
		$components = parse_url($uri);
		
		if(!array_key_exists("path", $components)){
			$components['path'] = "";
		}
		
		if(!array_key_exists("query", $components)){
			$components['query'] = "";
		}
		
		return $components;
	}
	
};

class Hydna{
	
	public $agent = "hydna-php-push";
	
	public static $TIMEOUT = 5;
	
	public function push($domain, $data, $prio=1, $ctoken=""){
		
		$headers = array('Content-Type: text/plain', sprintf('User-Agent: %s', $this->agent));
		
		if(!empty($ctoken)){
			$token = HydnaUtil::clean_token($ctoken);
			$headers[] = sprintf('X-Token: %s', $token);
		}
		
		if(!empty($prio)){
			$prio = HydnaUtil::clean_prio($prio);
			$headers[] = sprintf('X-Priority: %s', $prio);
		}
		
		return $this->send($domain, $headers, $data);
	}
	
	public function emit($domain, $signal, $ctoken=""){

		$headers = array('X-Emit: yes', 'Content-Type: text/plain', sprintf('User-Agent: %s',$this->agent));
		
		if(!empty($ctoken)){
			$token = HydnaUtil::clean_token($ctoken);
			$headers[] = sprintf('X-Token: %s', $token);
		}
		
		return $this->send($domain, $headers, $signal);
	}
	
	private function send($url, $headers, $data){
		
		if (!extension_loaded('curl')){
			die('Sorry cURL is not installed!');
		}
		
		$uri = HydnaUtil::parse_uri($url);
		
		$curl_handle = curl_init();
		
		$conn = sprintf('%s://%s/%d/', $uri['scheme'], $uri['host'], $uri['channel']);
		
		if(!empty($uri['token'])){
			$conn .= sprintf('?%s', $uri['token']);
		}
		
		curl_setopt($curl_handle, CURLOPT_URL, $conn);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, Hydna::$TIMEOUT);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
			
		$data = HydnaUtil::clean_data($data);
			
		curl_setopt($curl_handle, CURLOPT_POST, true);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
		
		$result = curl_exec($curl_handle);
		
		$code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		
		curl_close($curl_handle);
		
		if( $code != 200 ){
			throw new Exception($result);
		}
		
		return true;
	}
	
}

?>