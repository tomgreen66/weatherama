<?php
/**
 * The base configuration
 *
 *
 * @package Condiment
 */
	define('DB_NAME', 'met');
	define('DB_USER', 'fahren');
	define('DB_PASS', 'byte1');
/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');
define('DB_CONNECTION', mysql_connect(DB_HOST, DB_USER, DB_PASS)); // open a connection to the database
mysql_set_charset(DB_CHARSET, DB_CONNECTION);
$db_selected = mysql_select_db(DB_NAME, DB_CONNECTION);

 
function __autoload($name) {
	//echo "Want to load $name.\n";
	if( include 'includes/' . $name . '.class.php'){
	}else{
		die('<b id="no autoload class">can\'t autoload: ' . $name . '</b>');
	}	
}

?>