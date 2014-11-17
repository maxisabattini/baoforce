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