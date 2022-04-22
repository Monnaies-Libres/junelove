<?php

require_once('DAO.class.php');

class CesiumPlus {
	
	private $dao;
	
	public function __construct () {
		
		$this->dao = DAO::getInstance();
	}
	
	
	public function getCesiumPlusProfile ($pubkey) {

		$json = $this->dao->fetchJson('/user/profile/' . $pubkey, 'cesiumplus');

		return json_decode($json);
	}
}
