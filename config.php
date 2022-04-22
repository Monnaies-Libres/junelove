<?php 

//define('DOMAIN', 'junelove');
define('DOMAIN', 'uploads.borispaing.fr');

$cookieOptions = [
	'expires' => time()+60*60*24*365, 
	'path' => '/',
	'domain' => DOMAIN,
	'secure' => false,
	'httponly' => false,
	'samesite' => 'Lax'
];

