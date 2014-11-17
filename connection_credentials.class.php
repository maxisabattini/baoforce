<?php

namespace baoforce;

use \baobab\Log;
use \baobab\Config;

class ConnectionCredentials {

	public $username;
	public $password;
	public $token;
	
	public $client_id;
	public $client_secret;
	
	public function __construct( $username, $password, $token, $client_id, $client_secret ) {
		$this->username=$username;
		$this->password=$password;
		$this->token=$token;
		$this->client_id=$client_id;
		$this->client_secret=$client_secret;
	}

	public static function getDefault(){
		return new ConnectionCredentials( self::$_default_user );
	}
	
	public static function getDefaultUser($user) {
		return self::$_default_user;
	}
	
	public static function setDefault($credentials) {
		self::$_default_user=$user;
	}	
}

class ConnectionCredentialsList {

	protected static $_list = array();
	protected static $_default_username="";
	
	public static function add($credential) {
		self::$_list[ $credential->username ]=$credential;
	}
	
	public static function remove($username) {
		unset(self::$_list[ $username ]);
	}

	public static function get($username){
		return self::$_list[ $username ];
	}
	
	
	public static function getDefault(){
		return self::$_list[ self::$_default_username ];
	}

	public static function setDefaultUsername($username) {
		self::$_default_username=$username;
	}
}

