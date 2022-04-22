<?php

require_once('DAO.class.php');

class G1 {
	
	private $dao;
	
	public function __construct () {
		
		$this->dao = DAO::getInstance();
	}
	
	public function getCertifiedBy ($pubkey) {
	
		$n = 15;
		
		$queryParams = [
			'size' => $n
			
		];
		
		$json = $this->dao->fetchJson('/wot/certified-by/' . $pubkey, 
		                              'g1');
		$result = json_decode($json);
		
		return array_column($result->certifications, 'pubkey');
	}
}
