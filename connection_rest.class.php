<?php

namespace baoforce;

use \baobab\Cache;
use \baobab\Log;

require_once "connection.class.php";
require_once "connection_credentials.class.php";

class ConnectionRest extends Connection {
	
	private $_credentials = null;
	
	private $_lastRequestStatus;

	private $_version="30.0";
		
	public function __construct( $credentials ){
	
		$this->_sessionLength=5000;
		
		if(!$credentials || !is_object($credentials)){
			Log::error( "No valid credentials" );
		} else {
			$this->_credentials=$credentials;
			$this->_readCached();
			if( $this->loginRequired() ) {
				$this->login();
			}
		}
	}
	
	public function login() {
	
		$auth_url = "https://na1.salesforce.com/services/oauth2/token";
		$params="grant_type=password"
	      ."&client_id=" . $this->_credentials->client_id
	      ."&client_secret=" . $this->_credentials->client_secret
	      ."&username=" . $this->_credentials->username
	      ."&password=" . $this->_credentials->password . $this->_credentials->token 
		;

		Log::info("Login with " . $this->_credentials->username);

		$curl = curl_init($auth_url);		
		curl_setopt($curl, CURLOPT_HEADER, false);		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		
		$this->_link = json_decode( curl_exec($curl) );

		$this->_lastRequestStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		if( is_object($this->_link) && ! isset($this->_link->error) ) {		
			$this->_sessionId = $this->_link->access_token;
			$this->_lastLoggedTime = microtime(true);
			$this->_link->lastLoggedTime=$this->_lastLoggedTime;			
			$this->_writeCached();
		} else {
			//Login error
			Log::warn("Can not login with " . $params);
			Log::warn("Can not login result " . print_r($this->_link, true));
			$this->_link = false;
		}
	}
	
	public function request($url, $content=array(), $customHeader=array() ){

		$result=$this->requestReal($url, $content, $customHeader );

		if( isset( $result["response"]->errorCode ) && $result["response"]->errorCode == "INVALID_SESSION_ID" ) {
			//Retry login
			$this->login();
			$result=$this->requestReal($url, $content, $customHeader );
		}
		
		return $result;
	}

	protected function requestReal($url, $content=array(), $customHeader=array() ){

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$headers = array("Authorization: OAuth " . $this->_sessionId );
		if($content) {
			$headers[]="Content-type: application/json";	

			if( is_object($content) ) {
				$content = (array) $content;
			}	

			if( is_array($content) ) {
				$content = json_encode($content);
			}
			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $content);	
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers	);
		
		if($customHeader) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $customHeader);	
		}

		$json_response = curl_exec($curl);
		
		$this->_lastRequestStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		$curl_error = curl_errno($curl);
		$curl_error_number = 0;
		if( $curl_error ) {
			$curl_error_number = curl_errno($curl);
			Log::debug( curl_error($curl) . ", curl_errno " . $curl_error_number );
		}
		
		curl_close($curl);

		$result = array(
			"error"		=> !!$curl_error,
			"error_code"	=> $curl_error_number,
			"http_code"	=> $this->_lastRequestStatus,			 
			"response"	=> json_decode($json_response),
		);
		
		return $result;
	}
	
	public function getLastRequestStatus(){
		return $this->_lastRequestStatus;
	}
	
	public function query($soql) {
		$link = $this->getLink();
		$url = $link->instance_url ."/services/data/v{$this->_version}/query?q=" . urlencode($soql);
		$result = $this->request($url);
		return $result["response"];
	}

	public function queryFirst($query){
		$response = $this->query($query);
		if( isset($response->done) ) {
			if( count($response->records) ) {
				return $response->records[0];
			}
		} else {
			Log::warn("queryFirst() error");
			Log::debug($response);
		}
		return false;
	}

	public function queryAll($query){
		$response = $this->query($query);
		if( isset($response->done) ) {
			if( isset($response->records) ) {
				return $response->records;
			}
		} else {
			Log::warn("queryAll() error");
			Log::debug($response);
		}
		return array();
	}
	
	public function create($objectName, $objectData ) {
		$link = $this->getLink();
		$url = $link->instance_url . "/services/data/v{$this->_version}/sobjects/$objectName/";
        
		$content = json_encode($objectData);
		$result = $this->request($url, $content);
		$response = $result["response"];
		if($result["http_code"]!="201") {
			Log::error("Create:");
			Log::debug($response);
			return false;
		}

		return $response->id;
	}
	
	public function update($objectName, $id, $objectData ) {
		$link = $this->getLink();
		$url = $link->instance_url . "/services/data/v{$this->_version}/sobjects/$objectName/$id";
		$content = json_encode($objectData);
        
		$result = $this->request($url, $content, "PATCH");
		\baobab\Log::debug($result);
		return $result["http_code"]=="204";
	}


	public function attach( $parentId,  $fileContent, $fileName ) {
		$link = $this->getLink();

		$url = $link->instance_url . "/services/data/v{$this->_version}/sobjects/Attachment/";

		$data = new \stdClass();
		$data->ParentId=$parentId;
		$data->Name=$fileName;
		$data->body=$fileContent;

		\baobab\Log::debug($data);
		$result = $this->request($url, $data);
		\baobab\Log::debug($result);
		return $result["http_code"]=="201";
	}

	public function show($objectName, $id ) {
	      $link = $this->getLink();
	      $url = $link->instance_url . "/services/data/v{$this->_version}/sobjects/$objectName/$id";
	      $result = $this->request($url);
	      return $result["response"];
	}
	
	public function delete($objectName, $id ) {
		$link = $this->getLink();
		$url = $link->instance_url ."/services/data/v{$this->_version}/sobjects/$objectName/$id";
		$result = $this->request($url, false, "DELETE");
		return $result["http_code"]=="204";
	}

	public function describe($objectName) {
		$link = $this->getLink();
		$url = $link->instance_url ."/services/data/v{$this->_version}/sobjects/$objectName/describe";
		$result = $this->request($url);
		return $result["response"];
	}

	public function describeLayouts($objectName, $id="" ) {
		$link = $this->getLink();
		$url = $link->instance_url ."/services/data/v{$this->_version}/sobjects/$objectName/describe/layouts";
		if($id) {
			$url = $url . "/$id";
		}
		$result = $this->request($url);
		return $result["response"];
	}

	public function versions() {
		$link = $this->getLink();
		$url = $link->instance_url ."/services/data/";
		$result = $this->request($url);
		return $result["response"];
	}
	
	private function _readCached() {
		$cache = Cache::getInstance();
		if(!$cache->isEnabled()) {
			return;
		}

		$data = $cache->get("ConnectionRest_".$this->_credentials->username );		
		if($data){
			$cached = unserialize($data);
			if( is_object($cached) && $cached->lastLoggedTime ) {				
				$this->_sessionId=$cached->access_token;
				$this->_link = $cached;
				$this->_lastLoggedTime = $cached->lastLoggedTime;
			}
		}
	}
	
	private function _writeCached() {
		$cache = Cache::getInstance();
		if(!$cache->isEnabled()) {
			return;
		}        
		$cache->set("ConnectionRest_".$this->_credentials->username, $data=serialize($this->_link), $this->_sessionLength );
	}	
}
