<?php


class Location {
	
	const API_URL = 'http://nominatim.openstreetmap.org/search?q=%s&format=json';
	
	
	private $lat;
	
	private $lon;
	
	private $name;
	
	private $city;
	
	private $postCode;
	
	private $successfulQuery;
	
	
	public function __construct () {
		
	}
	
	public function fetchOpenStreetMap ($searchQuery) {
	
		$json = NULL;
		
		$streamContext = stream_context_create(
			array(
				"http" => array(
					"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
				)
			)
		);
		
		$url = sprintf(Location::API_URL, urlencode($searchQuery));
		$json = @file_get_contents($url, false, $streamContext);
		
		if (!empty($json)) {
			
			$json = json_decode($json);
		}
		
		return $json;
	}
	
	public function createFromAddress ($searchTerms) {
		
		if (is_array($searchTerms)) {
			
			while (!empty($searchTerms)) {
				
				$searchQuery = implode(' ', $searchTerms);
				$results = Location::fetchOpenStreetMap($searchQuery);
				
				if (empty($json)) {
					
					$searchTerms = array_slice($searchTerms, 0, -1);
				
				} else {
					
					break;
				}
			}
						     
		} else {
			
			$searchQuery = $searchTerms;
			$results = Location::fetchOpenStreetMap($searchQuery);
		}
		
		if (isset($results[0])) {
		
			$firstResult = $results[0];
			
			$loc = new Location();
			
			$loc->setPosition($firstResult->lat, $firstResult->lon);
			$loc->successfulQuery = $searchQuery;
			
			return $loc;
		
		} else {
		
			return false;
		}
	}
	
	public function setPosition ($lat, $lon) {
		
		$this->lat = $lat;
		$this->lon = $lon;
	}
	
	public function getPosition () {

		return [$this->lat, $this->lon];
	}
	
	public function getSuccessfulQuery () {

		return $this->successfulQuery;
	}
	
	
	public function getLat () {

		return $this->lat;
	}
	
	
	public function getLon () {

		return $this->lon;
	}
	
	/* 
	 * Retourne la distance (en km) entre les deux points.
	 * $pos1[x, y]
	 * $pos2[x, y]
	 */
	 
	static public function geoDist ($pos1, $pos2) {
		
		// https://stackoverflow.com/questions/365826/calculate-distance-between-2-gps-coordinates
		
		$a = sin(deg2rad($pos2[0]-$pos1[0])/2)**2 + sin(deg2rad($pos2[1]-$pos1[1])/2)**2 * cos(deg2rad($pos1[0])) * cos(deg2rad($pos2[0]));
		
		return 12742 * atan2(sqrt($a), sqrt(1-$a));
	}
	
}
