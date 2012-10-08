<?php
/**
 * Cache - handles caching data in and out of the database
 * @version 0.1
 * @package condiment
 * @subpackage Cache
 * @author David Harris <theinternetlab@gmail.com>
 */

//! TODO - change mysql table to use HASH index and make the identifier the full text not the calculated hash
//! should see a performance increase

class Cache{
	
	private $id; //32 character md5 type identifier
	private $expires;
	
	// url - full URL of page - including domain & GET variables
	function __construct($identifier){
		$this->expires = 60*60*24;
		$this->id = $identifier;
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function setExpirey($expirey){
		$this->expires = $expirey;
	}
	
	public function updateCache($data){
		
		$q = "REPLACE INTO cache SET cache = '" . mysql_real_escape_string(serialize($data)). "' , cache_id = '" . $this->id . "'";
		Common::sendQuery($q);
	}
	
	public function getCache(){
		$q = "SELECT cache FROM cache WHERE cache_id = '" . $this->id . "' AND TIMESTAMPADD(SECOND," . $this->expires . ",stamp) > NOW() LIMIT 1";
		$rows = Common::getRows($q);
		if( count($rows) == 1 ){		
			return unserialize($rows[0]['cache']);
		}else{
			return false;
		}
		
	}
	
}

?>