<?php
/**
 * Billmate
 *
 * Billmate API - PHP Class 
 *
 * LICENSE: This source file is part of Billmate API, that is fully owned by Billmate AB
 * This is not open source. For licensing queries, please contact efinance at info@billmate.se.
 *
 * @category Billmate
 * @package Billmate
 * @author Yuksel Findik <yuksel@billmate.se>
 * @copyright 2013-2014 Billmate AB
 * @license Proprietary and fully owned by Billmate AB
 * @version 1.1
 * @link http://www.billmate.se
 *
 * History:
 * 2.0 20140625 Yuksel Findik: Second Version
 * 2.0.8 20141125 Yuksel Findik: Url is updated. Some variables are updated
 * 2.0.9 20141204 Yuksel Findik: Returns array and verifies the data is safe
 * 2.1.0 20141215 Yuksel Findik: Unnecessary variables are removed
 * 2.1.1 20141218 Yuksel Findik: If response can not be json_decoded, will return actuall response
 * 2.1.2.20150107 Jesper Johansson verify_hash function taxes the post from notify and accepturls and verifies hash
 * 									usage: Billmage->verify_hash($_POST); Returns error code 9511 when hash failing.
 */
class BillMate{
	var $ID = "";
	var $KEY = "";
	var $URL = "api.billmate.se";
	var $MODE = "CURL";
	var $SSL = true;
	var $TEST = false;
	var $DEBUG = false;
	function BillMate($id,$key,$ssl=true,$test=false,$debug=false){
		$this->ID = $id;
		$this->KEY = $key;
        defined('BILLMATE_CLIENT') || define('BILLMATE_CLIENT',  "BillMate:2.1.1" );
        defined('BILLMATE_SERVER') || define('BILLMATE_SERVER',  "2.0.6" );
		$this->SSL = $ssl;
		$this->DEBUG = $debug;
		$this->TEST = $test;
	}
	public function __call($name,$args){
	 	if(count($args)==0) return; //Function call should be skipped
	 	return $this->call($name,$args[0]);
	}
	function call($function,$params) {
		$values = array(
			"credentials" => array(
				"id"=>$this->ID,
				"hash"=>$this->hash(json_encode($params)),
				"version"=>BILLMATE_SERVER,
				"client"=>BILLMATE_CLIENT,
				"useragent"=>$_SERVER['HTTP_USER_AGENT'],
				"time"=>microtime(true),
				"test"=>$this->TEST?"1":"0",
			),
			"data"=> $params,
			"function"=>$function,
		);

		$this->out("CALLED FUNCTION",$function);
		$this->out("PARAMETERS TO BE SENT",$values);
		switch ($this->MODE) {
			case "CURL":
				$response = $this->curl(json_encode($values));
				break;
		}
		$response_array = json_decode($response,true);
		if(!$response_array) return $response;
		if(isset($response_array["credentials"])){
			$hash = $this->hash(json_encode($response_array["data"]));
			if($response_array["credentials"]["hash"]==$hash)
				return $response_array["data"];
			else return array("error"=>9511,"message"=>"Verification error","hash"=>$hash,"hash_received"=>$response_array["credentials"]["hash"]);
		}
		return $response_array;
	}
	function curl($parameters) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http".($this->SSL?"s":"")."://".$this->URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSL);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->SSL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		    'Content-Type: application/json',                                                                                
		    'Content-Length: ' . strlen($parameters))                                                                       
		);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		$data = curl_exec($ch);
		
		if (curl_errno($ch)){
	        $curlerror = curl_error($ch);
	        return json_encode(array("error"=>9510,"message"=>htmlentities($curlerror)));
		}else curl_close($ch);
		
	    return $data;
	}

	/**
	 * A Method for verify hash response
	 * @param $post The post data
	 *
	 * @return mixed true|false
	 */
	public function verify_hash($response) {
		$response_array['data'] = json_decode(stripslashes($response['data']),true);
		$response_array['credentials'] = json_decode(stripslashes($response_array['credentials']),true);
		if(!$response_array) return $response;
		if(isset($response_array["credentials"])){
			$hash = $this->hash(json_encode($response_array["data"]));
			if($response_array["credentials"]["hash"]==$hash)
				return $response_array["data"];
			else return array("error"=>9511,"message"=>"Verification error","hash"=>$hash,"hash_received"=>$response_array["credentials"]["hash"]);
		}
		return $response_array;

	}
	function hash($args) {
		$this->out("TO BE HASHED DATA",$args);
    	return hash_hmac('sha512',$args,$this->KEY);
    }
    function out($name,$out) {
    	if (!$this->DEBUG) return;
    	print "$name: '";
    	if(is_array($out) or  is_object($out)) print_r($out);
    	else print $out;
    	print "'\n";
    }
    
}
?>
