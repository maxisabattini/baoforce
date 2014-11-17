<?php

namespace baoforce;

class Connection {

	protected $_sessionId;
	protected $_sessionLength=5000;	
	protected $_lastLoggedTime;
	
	protected $_link;
	
	public function getLink(){
		return $this->_link;
	}
	
	protected function login(){}
	
	protected function isConnected(){
		$diff = microtime(true) - $this->_lastLoggedTime;
		return $diff < $this->_sessionLength;
	}

	protected function loginRequired() {
		if (!$this->_sessionId) {
			return true;	
		}
		return !$this->isConnected();
	}	
}

