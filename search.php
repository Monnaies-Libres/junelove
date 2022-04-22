<?php
$debug = '';

require_once('config.php');
require_once('lib/DAO.class.php');
require_once('lib/G1.class.php');
require_once('lib/CesiumPlus.class.php');
require_once('lib/Location.class.php');


if (!isset($_COOKIE['me']) and !isset($_POST['me'])) {
	
	header('Location: index.php');
	
} elseif (!isset($_POST['me'])) {
	
	$me = $_COOKIE['me'];

} else {
	
	$me = $_POST['me'];
	setcookie('me', $_POST['me'], $cookieOptions);
}

$meArray = [$me];

function getMatches () {

	if (empty($_COOKIE['match'])) {

		$matches = array();

	} else {
		
	    foreach ($_COOKIE['match'] as $pubkey => $match) {
	    
		    $matches[$pubkey] = $match;
		    
	    }
	}
	
	return $matches;
}

function discard ($pubkey) {
	
	global $cookieOptions;
		
	setcookie('match['. $pubkey .']', 'false', $cookieOptions) or die('erreur cookie');	
	
}

function match ($pubkey) {
	
	global $cookieOptions;
		
	setcookie('match['. $pubkey .']', 'true', $cookieOptions) or die('erreur cookie');	
	
}


$matches = array_keys(getMatches());

if (isset($_POST['discard'])) {
	
	discard($_POST['discard']);
	$matches[] = $_POST['discard'];
}

if (isset($_POST['match'])) {
	
	match($_POST['match']);
	$matches[] = $_POST['match'];
}




$g1 = new G1();
$certifiedByMe = $g1->getCertifiedBy($me);
$nbCertifiedByMe = count($certifiedByMe);

// $debug .= '<pre>matches :' . "\n\n" . print_r($matches, true) . '</pre>';
// $debug .= '<pre>certifiedByMe :' . "\n\n" . print_r($certifiedByMe, true) . '</pre>';

$friendsOfFriends = array();

$cesium = new CesiumPlus();

for ($i = 0; empty($friendsOfFriends) and $i < $nbCertifiedByMe ; ++$i) {
	
	$friendsOfFriends = array_merge($friendsOfFriends, $g1->getCertifiedBy($certifiedByMe[$i]));
	
	$friendsOfFriends = array_diff($friendsOfFriends, $certifiedByMe, $meArray, $matches);
	
	$soulmateProfile = NULL;
	
	 while (empty($soulmateProfile) and !empty($friendsOfFriends)) {
	
		$debug .= '<pre>friendsOfFriends ('. count($friendsOfFriends) . ') :' . "\n\n" . print_r($friendsOfFriends, true) . '</pre>';
		
		$debug .= 'i : ' . $i . '<br/>';

		$soulmate = current($friendsOfFriends);
	
		$connectionPath = [
			$me,
			$certifiedByMe[$i], 
			$soulmate
		];
		
		$soulmateProfile = $cesium->getCesiumPlusProfile($soulmate)->_source;
		
		$debug .= '<pre>soulmateProfile : ' . print_r($soulmateProfile, true) .'</pre>';
		
		if (empty($soulmateProfile)) {
			
			$debug .= 'discard ' . $soulmate . '<br />'; 
			
			discard(urlencode($soulmate));
			array_shift($friendsOfFriends);
		}
		
	}
}



// $debug .= '<pre>' . print_r($soulmateProfile, true) . </pre>';


echo '<p><a href="matches.php">Mes matches</a></p>';

echo '<h2>' . $soulmateProfile->title . '</h2>';

echo '<p>';
	$myPos = $cesium->getCesiumPlusProfile($me)->_source->geoPoint;
	$smPos = $soulmateProfile->geoPoint;
	if (isset($myPost, $soulmatePos)) {
	
	
			
		$myPoint = [(float) $myPos->lat, (float) $myPos->lon];
		$smPoint = [(float) $smPos->lat, (float) $smPos->lon];

		echo round(Location::geoDist($myPoint, $smPoint)) . ' km';
	}
	
	if (isset($soulmateProfile->city)) {
	
		echo '
		 ('. $soulmateProfile->city .')';
	}

	echo '
</p>';

echo '
<form method="post" action="">
	<input type="submit" value="Passer" />
	<input type="hidden" name="discard" value="'. $soulmate .'" />
	<input type="hidden" name="me" value="'. $me .'" />
</form>

<form method="post" action="">
	<input type="submit" value="Matcher" />
	<input type="hidden" name="match" value="'. $soulmate .'" />
	<input type="hidden" name="me" value="'. $me .'" />
</form>';

if (isset($soulmateProfile->avatar)) {
	
	echo '<img src="data:'. $soulmateProfile->avatar->_content_type . ';base64, '. $soulmateProfile->avatar->_content . '" />';
}


if (isset($soulmateProfile->description)) {

	echo '<p>Description :</p>
	<blockquote>' . nl2br($soulmateProfile->description) . '</blockquote>';
}

echo '<p>Chemin de rencontre : ';
$first = true;
foreach ($connectionPath as $p) {
	
	if (!$first) {
		echo ' » ';
	}
	
	$first = false;
	
	echo $cesium->getCesiumPlusProfile($p)->_source->title;
}

echo '</p>';

echo '<p>Sa clef publique : <a href="https://demo.cesium.app/#/app/wot/'. $soulmate . '">'. $soulmate . '</a></p>';

// echo $debug;
