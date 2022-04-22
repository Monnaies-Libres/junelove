<?php 


require_once('config.php');
require_once('lib/DAO.class.php');
require_once('lib/G1.class.php');
require_once('lib/CesiumPlus.class.php');


foreach ($_COOKIE['match'] as $pubkey => $match) {
	
	if ($match == 'true') {
	    
	    $matches[$pubkey] = $pubkey;
    }
}

$cesium = new CesiumPlus();
echo '<p><a href="search.php">Chercher l\'âme soeur</a></p>';

echo '<h1>Mes matches</h1>';
echo '<ul>';
foreach ($matches as $match) {
	
	$matchProfile = $cesium->getCesiumPlusProfile($match)->_source;
	
	echo '
	<li>';
		if (isset($matchProfile->avatar)) {
			
			echo '<img src="data:'. $matchProfile->avatar->_content_type . ';base64, '. $matchProfile->avatar->_content . '" />';
		}
		echo $matchProfile->title . '
	</li>';
}
echo '</ul>';

