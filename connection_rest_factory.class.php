<?php

namespace baoforce;

require_once "connection_rest.class.php";

class ConnectionRestFactory {

	protected static $_list = array();
	protected static $_default_username="";
	
	/**
	* Get instance
	*
	* @return ConnectionRest
	*/
	public static function getInstance( $username = "default") {
		static $instances=array();
		
		if( !$instances || !$instances[ $username ] ) {
			$instances[ $username ] = new ConnectionRest(
			  $username == "default" ? self::getDefaultCredentials() :
			  self::getCredentials($username) 
			);
		}		
		return $instances[ $username ];
	}	
	
	public static function addCredentials($credential) {
		self::$_list[ $credential->username ]=$credential;
	}
	
	public static function removeCredentials($username) {
		unset(self::$_list[ $username ]);
	}

	public static function getCredentials($username){
		return self::$_list[ $username ];
	}	
	
	public static function getDefaultCredentials(){
		return self::$_list[ self::$_default_username ];
	}

	public static function setDefaultUsername($username) {
		self::$_default_username=$username;
	}
}