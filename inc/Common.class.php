<?php
/**
 * Cache - handles caching data in and out of the database
 * @version 0.1
 * @package condiment
 * @subpackage Cache
 * @author David Harris <theinternetlab@gmail.com>
 */

class Common{

	public static function getRows($query,$key = false, $withFoundRows = false){
	// returns an associated array result from a mysql query ready to be "foreach"-ed through
	// $key will add a value as the key to each arrays row
	// otherwise is just incremented
		
	//	$timeparts = explode(' ',microtime());
	//	$starttime = $timeparts[1].substr($timeparts[0],1);
		
		$return = array();
		$result = mysql_query($query, DB_CONNECTION) or die('Database error' . mysql_error() . "<!--  ::\r\n\r\n" . $query . "\r\n\r\n -->");
		while( $row = mysql_fetch_array($result, MYSQL_ASSOC) ){
			if($key != false){
				$return[ $row[$key] ] = $row;
			}else{
				$return[] = $row;
			}
		}
		if($withFoundRows){
			$result = mysql_query("SELECT FOUND_ROWS() AS numrows LIMIT 1;", DB_CONNECTION);
			while( $row = mysql_fetch_array($result, MYSQL_ASSOC) ){
				$found = $row['numrows'];
			}
			$return['NUM_ROWS'] = $found;
		}
	//	$timeparts = explode(' ',microtime());
	//	$endtime = $timeparts[1].substr($timeparts[0],1);
	//	$_debug['query'][] = array('time' => $endtime - $starttime . ' secs', 'query' => $query);
		
		return $return;
	
	}
	
	
	public static function toUrl($str){
	//translates a page's title(or any string) into a url 
		$translate = array(
		' '=>'-','/'=>'-',
		//the ¤ strings are to be deleted
		':'=>'¤',';'=>'¤',"'"=>'¤','"'=>'¤','\\'=>'¤','.'=>'¤',','=>'¤','|'=>'¤','&'=>'¤','%'=>'¤','='=>'¤','+'=>'¤','<'=>'¤','>'=>'¤','?'=>'¤','^'=>'¤','£'=>'¤','$'=>'¤','@'=>'¤','!'=>'¤'
		);
		$url = strtr($str,$translate);
		//delete the ¤ strings
		$url = str_replace('¤','',$url);
		$url = strtolower($url);
		$regex = '/[^a-zA-Z0-9]+/';
		$url = preg_replace($regex, '-', $url);
		$url = str_replace('--','-',$url);
		return $url;
	}
	
/**
  * for INSERT, UPDATE and DELETE
  * returns the INSERT id OR number of rows affected or an error string on failure
**/
	public static function sendQuery($query){
		$result = mysql_query($query, DB_CONNECTION);
		
		$er = mysql_errno(DB_CONNECTION);
			
		if(!$er){
			if( substr($query, 0,6) == 'INSERT'){
				$return = mysql_insert_id(DB_CONNECTION);
			}else{
				$return = mysql_affected_rows(DB_CONNECTION);
			}
		}else{
		//echo 'q= ' . $query;
		mail('spakment@yahoo.co.uk', 'Database error condiment CMS' , $query . "\r\n" . var_export($_SERVER,true) . "\r\n" . var_export($_debug,true) );
			switch($er) {
				case 1062 :
					$return = 'duplicate';
					break;
				case 1065 :
					$return = 'empty';
					break;
				default :
					$return = 'mysqlError';
					if(DEBUG) {
						die(' er:' . mysql_error());
					}
					break;
			}
		}
		
		return $return;
	}
	
	
	
	
	/**
	  * for INSERT or UPDATE 
	  * returns the databse query for adding the $data into the $table
	**/
	function makeUpsertQuery($data , $table, $action = 'insert', $parameters = '') {
		reset($data);
		
		if ($action == 'insert' || $action == 'replace') {
			if($action == 'replace') {
				$query = 'REPLACE INTO ' . $table . ' (';
			}else{
				$query = 'INSERT INTO ' . $table . ' (';
			}
			
			while (list($columns, ) = each($data)) {
				$query .= '`' . $columns . '`, ';
			}
			$query = substr($query, 0, -2) . ') VALUES (';
			
			reset($data);
			
			while (list(, $value) = each($data)) {
				switch ((string)$value) {
					case 'NOW()':
						$query .= 'NOW(), ';
						break;
					case 'NULL':
						$query .= 'NULL, ';
						break;
					default:
						$query .= "'" . mysql_real_escape_string($value) . "', ";
					break;
				}
			}
			
			$query = substr($query, 0, -2) . ')';
		
		} elseif ($action == 'update') {
			
			$query = 'UPDATE ' . $table . ' SET    ';
			
			while (list($columns, $value) = each($data)) {
				switch ((string)$value) {
					case 'NOW()':
						$query .= '`' . $columns . '` = NOW(), ';
						break;
					case 'NULL':
						$query .= '`' . $columns .= '` = NULL, ';
						break;
					default:
						$query .= '`' . $columns . '` = \'' . mysql_real_escape_string($value) . '\', ';
						break;
				}
			}
			$query = substr($query, 0, -2) . ' WHERE ' . $parameters;
		}
		
		return $query;
	}

	function delayedRedirect($url, $message, $emptyCache = false, $delay = 3) {

		if($emptyCache) {
			$url = addUrlArg($url, '?cache-empty');
		}
		
		// check if the session id has been appended by php - ie no cookies enabled
		if(isset($_GET[SESSION_NAME])) {
			$url .= '?' . SESSION_NAME . '=' . session_id();
		}elseif(isset($_POST[SESSION_NAME])) {
			$url .= '?' . SESSION_NAME . '=' . session_id();
		}
		
		//$template = $langHtml->getVar('redirectHtml');//  file_get_contents('templates/inc/redirect.tpl.php');
		//$html = sprintf($template, $message, $delay, $url);
		
		$template = file_get_contents('views/admin/redirect.tpl.php');
		$html = sprintf($template, $message, $url, $delay );
		
		
		header('Refresh: ' . $delay . '; url=' . $url);
		header("Pragma: no-cache" );
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1				
		
		echo $html;
		exit();
		die();
	}
	
	
	function redirect($where) {
	
		// check if the session id has been appended by php - ie no cookies enabled
		if(isset($_GET[SESSION_NAME])) {
			$where .= '?' . SESSION_NAME . '=' . session_id();
		}elseif(isset($_POST[SESSION_NAME])) {
			$where .= '?' . SESSION_NAME . '=' . session_id();
		}
		
		header('Location: ' . $where);
		exit();
	}
	
	public static function secureHash($str){
		if( !(defined('AUTH_SALT')) ){
			die('insecure passwords - check setup for salt');
		}
		return hash('md4', $str . AUTH_SALT);
	}
	
	public static function snippetFromContent($str, $words = 50){
		$str = strip_tags($str);
		/*
		$expl = explode(' ', $str);
		$short = array_slice($expl, 0, $words);
		$return = implode(' ', $short);
		*/
		if( strlen($str) > $words*7){
			$return = substr($str,0, strpos($str, ' ', ($words*7) ));
		}else{
			$return = $str;
		}
		
		return $return;
	}	
	 
	// convert special characters but not the tags
	public static function textToHTML($str){
		return htmlspecialchars_decode( htmlentities( $str , ENT_NOQUOTES, 'UTF-8', false) , ENT_NOQUOTES );
	}

 
	//http://semlabs.co.uk/journal/converting-nested-set-model-data-in-to-multi-dimensional-arrays-in-php
	// nested set to multidimensional array
	// http://stackoverflow.com/questions/1606788/php-form-a-multi-dimensional-array-from-a-nested-set-model-flat-array
	public static function nestToMulti( $arr, $depth_key = 'section_level'){
		$p = array(array());
		foreach($arr as $n => $a) {
				$d = $a[$depth_key] + 1;
				$p[$d - 1]['children'][] = &$arr[$n];
				$p[$d] = &$arr[$n];
		}
		
		// dont return just a root node
//		if( is_array( $p[0]['children'][0]['children'] ) ){
//			return $p[0]['children'][0]['children']; //return children of the root
//		}else
		
		if(is_array($p)){
			return $p;
		}else{
			return false;
		}
	}
	
	public static function multiToList( $array, $name = 'section_name', $children = 'children'){
	    $var = '<ul>';
	    foreach ($array as $k => $v){
				$var .= '<li>';
	            if (is_array($v[$children]) && !empty($v[$children])) {
	            	$var .= $v[$name];
	                $var .= Common::multiToList($v[$children]);
	            }else{
	                $var .= $v[$name];
	            }
	            $var .= '</li>';
	    }
	
	    $var.= '</ul>';
	    return $var;
	}
	
	public static function addHttp($url){
		if (false === strpos($url, '://')) {
	    	$url = 'http://' . $url;
		}
		return $url;
	}
	
	public function characterAllow($string, $allowed = array(),$replace = '') {
		$allow = null;
		if (!empty($allowed)) {
			foreach ($allowed as $value) {
				$allow .= "\\$value";
			}
		}

		if (is_array($string)) {
			$cleaned = array();
			foreach ($string as $key => $clean) {
				$cleaned[$key] = preg_replace("/[^{$allow}a-zA-Z0-9]/", $replace, $clean);
			}
		} else {
			$cleaned = preg_replace("/[^{$allow}a-zA-Z0-9]/", $replace, $string);
		}
		return $cleaned;
	}
	
	public static function hashit($num,$mult=42,$add=101,$scale = 1000,$pre = 'tw',$post='te'){
		$num = (int)$num;
		$mask = ($num * $mult * $scale) + $add;
		$hash = base_convert($mask, 10, 36);
		return $pre . $hash . $post;
	}

	public static function unhashit($hash,$mult=42,$add=101,$scale = 1000,$pre = 'tw',$post='te'){
		$strip = substr($hash, strlen($pre), strlen($hash) - strlen($post) - strlen($pre) );
		$masked = base_convert($strip, 36, 10);
		$num = ($masked - $add) / ($mult * $scale);
		return (int)$num;
	}
	
 
}

?>