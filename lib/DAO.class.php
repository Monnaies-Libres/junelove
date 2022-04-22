<?php

date_default_timezone_set('Europe/Paris');

class DAO {

	/**********************
	 * Constants
	 **********************/

	const PUBKEY_FORMAT = '#^[a-zA-Z1-9]{43,44}$#';

	const DATE_FORMAT = 'Y-m-d';

	private $units = ['quantitative','relative'];

	private $truePossibleValues = ['true','1', 'yes'];

	private $qrCodesFolder = __DIR__ . '/img/qrcodes';

	private $qrCodePath = NULL;

	private $logosFolder = __DIR__ . '/img/logos';

	private $logo = NULL;

	private $logoPath = NULL;

	private $validDisplayTypes = ['img', 'svg', 'html'];
	
	private $cacheDir = __DIR__ . '/../cache/';
	
	private $isActivatedCache = true;
	
	private $cacheLongevity = 10800; // in seconds
	
	public static $dao;
	
	/**********************
	 * General parameters
	 **********************/

	private $pubkey;
	
	private $nodes = [
		
		'gchange' => [
		
			'data.gchange.fr'
		], 
		
		'cesiumplus' => [
			'g1.data.presles.fr',
			'g1.data.adn.life',
			'g1.data.duniter.fr',
			'g1.data.le-sou.org'
		], 
		
		'g1' => [
			'g1.mithril.re',
			'duniter.vincentux.fr',
		]
	];
	
	private $testURIs =  [
		
		'gchange' => '', 
		
		'cesiumplus' => '/user/profile/DsEx1pS33vzYZg4MroyBV9hCw98j1gtHEhwiZ5tK7ech', 
		
		'g1' => '/node/summary', 
	];
	
	private $nodeTimeout = [
		
		'g1' => 5,
		'cesiumplus' => 10,
		'gchange' => 5, 
	];
	
	private $nodeTimeoutIncrement = [
		
		'g1' => 2, 
		'cesiumplus' => 5, 
		'gchange' => 10
	];
	
	private $node = NULL;
	
	private $unit = 'quantitative';
	 

	/**********************
	 * Methods
	 **********************/
	
	public function __construct () {
		
	}
	
	public function getInstance () {
		
		if (!isset(DAO::$dao)) {
			
			DAO::$dao = new DAO();
		}
		
		return DAO::$dao;
	}

	private function setUnit ($unit) {

		if (!empty($unit)) {

			if (!in_array($unit, $this->units)) {

				$out = [];
				$out[] = _('L\'unité renseignée n\'existe pas.');
				$out[] = _('Vérifiez votre synthaxe.');

				$this->decease($out);

			} else {

				$this->unit = $unit;
			}
		}
	}

	public function decease ($errorMsgs) {

		if (!is_array($errorMsgs)) {

			$errorMsgs = explode("\n", $errorMsgs);
		}


		if ($this->displayType == 'img') {

			$source = imagecreatetruecolor(500, 200);

			$bgColor = imagecolorallocate($source,
										  255, 255, 255);

			imagefill($source,
					  0, 0,
					  $bgColor);

			$txtColor = imagecolorallocate($source,
										   0, 0, 0);

			$errorMsgFontSize = 3;
			$x = 5;
			$y = 5;

			foreach ($errorMsgs as $msg) {

				imagestring($source, $errorMsgFontSize, $x, $y, utf8_decode($msg), $txtColor);

				$y += $errorMsgFontSize + 20;
			}


			imagepng($source);
			imagedestroy($source);

		} else if ($this->displayType == 'svg') {

			echo '<?xml version="1.0" encoding="utf-8"?>
			<svg width="580"
				 height="224"
				 style="fill:black;"
				 version="1.1"
				 xmlns="http://www.w3.org/2000/svg"
				 xmlns:xlink="http://www.w3.org/1999/xlink">

				<g style="font-family:sans-serif;">';

				$x = 25;
				$y = 25;

				foreach ($errorMsgs as $msg) {

					echo '
					<text
						style="font-size:.8rem;"
						x="'. $x .'"
						y="'. $y . '"
						dominant-baseline="hanging">
							'. $msg . '
					</text>';

					$y += 25;
				}

				echo '
				</g>
			</svg>';

		} else {

			ob_get_clean(); // to prevent error message to display inside an HTML container (case of error generated by get method calls)

			echo '<!DOCTYPE html>
			<html>
				<head>
					<meta charset="utf-8" />
					<title>'. _('Erreur critique') . '</title>

					<style>

						div {

							overflow: auto;
							word-wrap: break-word;
							background-color: hsl(0, 100%, 69%);
							color: hsl(0, 100%, 19%);
							margin: 1em;
							padding: 1em;
							border-radius: 1em;
							position: fixed;
							top: 0;
							left: 0;
							width: calc(100% - 4em);
							max-height: calc(100vh - 4em);
						}
					</style>
				</head>

				<body>
					<div>';


						foreach ($errorMsgs as $msg) {

							echo '<p>' . $msg . '</p>';
						}

					echo '
					</div>
				</body>
			</html>';
		}

		exit;
	}

	public function printUnit () {

		if ($this->unit == 'relative') {

			if ($this->displayType == 'img') {

				return _('DUĞ1');

			} else {

				return _('DU<sub>Ğ1</sub>');
			}

		} else {

			return _('Ğ1');
		}
	}

	public function convertIntoChosenUnit ($amountInQuantitative) {

		if ($this->unit == 'quantitative') {

			return $amountInQuantitative;

		} else {
			
			if (!isset($this->startDateUdAmount)) {
			
				$this->startDateUdAmount = $this->getUdAmount($this->startDate);
			}
			
			return round($amountInQuantitative / $this->startDateUdAmount, 2);
		}
	}



	public function addNode ($node) {

		$node = htmlspecialchars($node);
		
		$this->nodes = array_unique(
		                            array_merge(
		                                        (array)$node, 
		                                        $this->nodes
		                                       )
		                           );
	}



	public function addNodes ($nodes) {
		
		if (!is_array($nodes)) {
	
			$nodes = explode(' ', $nodes);
		}
		
		foreach ($nodes as $node) {
		
			$this->addNode($node);
		}
		
	}
	
	/** 
	 * @return $nodes array
	 */
	public function getNodesList ($nodeType = 'g1') {
		
		switch ($nodeType) {
			
			case 'gchange':
				$nodesFilename = 'nodes-gchange';
				break;
			case 'cesiumplus':
				$nodesFilename = 'nodes-cesiumplus';
				break;
			default: 
				$nodesFilename = 'nodes';
				break;
		}
		
		$nodesFilename .=  '.txt';
		$nodesFullpath = $this->cacheDir . $nodesFilename;
		
		$nodes = $this->nodes[$nodeType];
		
		if ($this->isActivatedCache) {
			
			if (!file_exists($nodesFullpath)) {
				
				shuffle($nodes);
				
				$this->cacheNodes($nodes, $nodeType);
				
				
			} else {
			
				$nodesStr = file_get_contents($nodesFullpath);
				
				$nodes = explode("\n", $nodesStr);
			}
		
		} else {
			
			shuffle($nodes);
			
		}
		
		return $nodes;
	}

	protected function cacheNodes ($nodes, $nodeType = 'g1') {
		
		switch ($nodeType) {
			
			case 'gchange':
				$nodesFilename = 'nodes-gchange';
				break;
			case 'cesiumplus':
				$nodesFilename = 'nodes-cesiumplus';
				break;
			default: 
				$nodesFilename = 'nodes';
				break;
		}
		$nodesFilename .=  '.txt';
	
		if (!file_exists($this->cacheDir)) {
			
			mkdir($this->cacheDir, 0777, true);
		
		}
	
		file_put_contents($this->cacheDir . $nodesFilename, implode("\n", $nodes));
	}

	protected function saveNodes ($nodes, $nodeType = 'g1') {
		
		$this->nodes[$nodeType] = $nodes;
	}
	
	protected function fetchJson_aux ($nodes, $uri, $nodeType, $queryParams, $nodesNb, $nodeTimeout) {
		
		// $header = 'Content-Type: application/x-www-form-urlencoded';
		// $header = "Content-Type: text/xml\r\n";
		
		if (!empty($queryParams)) {
			
			$opts = [
				'http' => [
					'method'  => 'POST',
					'content' => json_encode($queryParams),
					// 'header'  => $header, 
					'timeout' => $nodeTimeout
				]
			];
		
		} else {
		
			$opts = [
				'http' => [
					'method'  => 'GET',
					'timeout' => $nodeTimeout
				]
			];
		
		}
			
			
		$streamContext = stream_context_create($opts);
		
		$i = 0;

		do {
			
			$foundValidNode = @file_get_contents("https://" . current($nodes) . $this->testURIs[$nodeType]);
			
			if ($foundValidNode) {
				
				$json = @file_get_contents("https://" . current($nodes) . $uri, 
					                   false,  
					                   $streamContext);
					                   
           } else {
				
				$nodes[] = array_shift($nodes);
				++$i;
			}
			
		} while (!$foundValidNode and ($i < $nodesNb));
		
		if ($foundValidNode) {
		
			// Let's save node order for other queries :
			$this->saveNodes($nodes, $nodeType);
			
			if ($this->isActivatedCache) {
			
				$this->cacheNodes($nodes, $nodeType);
			}
		}
		
		return array($foundValidNode, $json);
	}
	
	
	public function fetchJson ($uri, $nodeType = 'g1', $queryParams = NULL) {

		$json = NULL;
		
		$nodes = $this->getNodesList($nodeType);
				
		$nodesNb = count($nodes);
		
		$maxTries = 3;
		
		$nodeTimeout = $this->nodeTimeout[$nodeType];
		$nodeTimeoutIncrement = $this->nodeTimeoutIncrement[$nodeType];
		
		$foundValidNode = false;
		
		for ($i = 0; ($i < 3) and !$foundValidNode; ++$i) {
			
			list($foundValidNode, $json) = $this->fetchJson_aux($nodes, $uri, $nodeType, $queryParams, $nodesNb, $nodeTimeout);
			
			$nodeTimeout += $nodeTimeoutIncrement;
		}
		
		if (!$foundValidNode) {
			
			$out = [];
			$out[] = _('Aucun noeud '. $nodeType .' n\'a été trouvé.');
			$out[] = _('Noeud interrogés : ');
			
			$out = array_merge($out, $nodes);
			
			if (isset($queryParams)) {
			
				$out[] = _('Paramètres de la requête : ');
				$out[] = print_r($queryParams, true);
			}
			
			$out[] = _('URI de la requête : ');
			$out[] = $uri;
			
			$this->decease($out);
		}

		return $json;
	}
	
	protected function fetchUdAmount ($date) {
		
		// On récupère les numéros de chaque blocks de DU journalier
		$json = $this->fetchJson('/blockchain/with/ud');
		$blocks = json_decode($json)->result->blocks;	
		
		if ($date > $this->now) {
			
			// On récupère le dernier block
			$blockNum = end($blocks);

		} else {
			
			// On récupère le bloc de la date qui nous intéresse
			$blockNum = $blocks[count($blocks) - $this->today->diff($date)->format("%a") - 1];
		}
		
		// Puis on récupère le montant du DU
		$json = $this->fetchJson('/blockchain/block/' . $blockNum);
		$block = json_decode($json);
		
		
		return ($block->dividend / 100);
	}
	
	public function getUdAmount ($date) {
		
		$udFilename = $this->getUdFilename($date);
		$udsCacheDir = $this->cacheDir . 'uds/';
		$udFullPath = $udsCacheDir . $udFilename;
		
		if ($this->isActivatedCache) {
		
			if (file_exists($udFullPath)) {
			
				$udCachedAmount = file_get_contents($udFullPath);
				
				if (is_numeric($udCachedAmount) and $udCachedAmount != 0) {
					
					$udAmount = floatval($udCachedAmount);
				}
			}
			
			
			
			if (!isset($udAmount)) {
				
				$udAmount = $this->fetchUdAmount($date);
				
				// Cache UD amount
				
				if (!file_exists($udsCacheDir)) {
					
					mkdir($udsCacheDir, 0777, true);
				
				}
				
				file_put_contents($udFullPath, $udAmount);
				
			}

		
		} else {
				
			$udAmount = $this->fetchUdAmount($date);
			
		}
		
		return $udAmount;
	}
	

	protected function getUdFilename ($date) {
		
		$datePreviousAutumnEquinox = new DateTime($date->format('Y') . '-09-22');
		$datePreviousSpringEquinox = new DateTime($date->format('Y') . '-03-20');

		if ($date > $datePreviousAutumnEquinox) {

			$udFilename = $date->format('Y') . '-autumn';

		} elseif ($date > $datePreviousSpringEquinox) {

			$udFilename = $date->format('Y') . '-spring';
			
		} else {

			$udFilename = ($date->sub(new DateInterval('P1Y'))->format('Y')). '-autumn';
		}

		return $udFilename . '.txt';

	}


}
