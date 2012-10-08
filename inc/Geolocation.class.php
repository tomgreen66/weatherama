<?php
/**
 * Geolocation - handles distance calc and getting geo info for searches
 * @version 0.1
 * @package condiment
 * @author David Harris <theinternetlab@gmail.com>
 */

class Geolocation{
	
	private $location;
	private $lat;
	private $lng;
	private $northing;
	private $easting;
	private $data;
	private $update = false;
	private $unique_id;
	private $look = true;
	private $lookedup;
	public $usedCache = false;
	
	function __construct($location){
		$this->location = $location;
		
		$this->unique_id = hash('md4', 'geolocation' . $this->location);
		//get Widgets cache
		$this->cache = new Cache($this->unique_id);
		$this->cache->setExpirey(5184000); //for testing - 1sec , live test 60*60*24*60 = 2 months = 5184000
		
		
	}
	
	// when script finishes update cache if needed
	function __destruct(){
		if($this->update){
			//var_dump($this->cacheData);
			$this->cache->updateCache( $this->data );
		}
	}
	
	private function update(){
		$this->update = true;
	}
	public function setLookup($bool){
		$this->look = $bool;
		if($bool == false){
			$this->update = false;
		}
	}
	public function setData($lat,$lng){
		$this->lookedup->results[0]->geometry->location->lat = $lat;
		$this->lookedup->results[0]->geometry->location->lng = $lng;
	}
	public function lookup(){
	
		if($this->data = $this->cache->getCache()){
		
			//echo '<p> using cached geocode ' . $this->location . '<p>';
			$this->usedCache = true;
		}else{
			//echo '<p> using Google ' . $this->location . '<p>';
			if($this->look){
				$googleJSON = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($this->location) . '&sensor=false');
				$this->lookedup = json_decode($googleJSON);
			}
			
			if( is_object($this->lookedup) ){
				$this->data['lat'] = $this->lookedup->results[0]->geometry->location->lat;
				$this->data['lng'] = $this->lookedup->results[0]->geometry->location->lng;
				$this->calcOSGrid();
				if($this->look && is_numeric($this->data['lat']) && is_numeric($this->data['lng']) ){
					$this->update();
				}
			}
		}
	}

	public function getLat(){
		return $this->data['lat'];
	}
	
	public function getLng(){
		return $this->data['lng'];
	}
	
	public function getOSNorth(){
		return $this->data['northing'];
	}
	
	public function getOSEast(){
		return $this->data['easting'];
	}
	
	
	public function calcOSGrid(){
		$OSGrid = $this->latLng2osGrid($this->data['lat'],$this->data['lng']);
		$this->data['easting'] = $OSGrid['E'];
		$this->data['northing'] = $OSGrid['N'];
	}
	
	/**
	  * Converts longitude & latitude to OS Northing and Easting
	  * modified from JavaScript version
	  * http://www.movable-type.co.uk/scripts/latlong-gridref.html
	  * Chris Veness <scripts-geo@movable-type.co.uk> 
	**/
	public static function latLng2osGrid($inLat, $inLon){

	 	$lat = $inLat * M_PI/180; // convert to rad
	 	$lon = $inLon * M_PI/180;
		
		$a = 6377563.396;
		$b = 6356256.910;    // Airy 1830 major & minor semi-axes
		$F0 = 0.9996012717;  // NatGrid scale factor on central meridian
		$lat0 = 49 * M_PI/180;
		$lon0 = -2 * M_PI/180;  // NatGrid true origin
		
		$N0 = -100000;
		$E0 = 400000;     // northing & easting of true origin, metres
		$e2 = 1 - ($b*$b)/($a*$a);  // eccentricity squared
		$n = ($a-$b)/($a+$b); 
		$n2 = $n*$n;
		$n3 = $n*$n*$n;
	
		$cosLat = cos($lat);
		$sinLat = sin($lat);
		$nu = $a*$F0/sqrt(1-$e2*$sinLat*$sinLat);              // transverse radius of curvature
		$rho = $a*$F0*(1-$e2)/pow(1-$e2*$sinLat*$sinLat, 1.5);  // meridional radius of curvature
		$eta2 = $nu/$rho-1;
		
		$Ma = (1 + $n + (5/4)*$n2 + (5/4)*$n3) * ($lat-$lat0);
		$Mb = (3*$n + 3*$n*$n + (21/8)*$n3) * sin($lat-$lat0) * cos($lat+$lat0);
		$Mc = ((15/8)*$n2 + (15/8)*$n3) * sin(2*($lat-$lat0)) * cos(2*($lat+$lat0));
		$Md = (35/24)*$n3 * sin(3*($lat-$lat0)) * cos(3*($lat+$lat0));
		$M = $b * $F0 * ($Ma - $Mb + $Mc - $Md);   // meridional arc
		
		$cos3lat = $cosLat*$cosLat*$cosLat;
		$cos5lat = $cos3lat*$cosLat*$cosLat;
		$tan2lat = tan($lat)*tan($lat);
		$tan4lat = $tan2lat*$tan2lat;
		
		$I = $M + $N0;
		$II = ($nu/2)*$sinLat*$cosLat;
		$III = ($nu/24)*$sinLat*$cos3lat*(5-$tan2lat+9*$eta2);
		$IIIA = ($nu/720)*$sinLat*$cos5lat*(61-58*$tan2lat+$tan4lat);
		$IV = $nu*$cosLat;
		$V = ($nu/6)*$cos3lat*($nu/$rho-$tan2lat);
		$VI = ($nu/120) * $cos5lat * (5 - 18*$tan2lat + $tan4lat + 14*$eta2 - 58*$tan2lat*$eta2);
		
		$dLon = $lon-$lon0;
		$dLon2 = $dLon*$dLon;
		$dLon3 = $dLon2*$dLon;
		$dLon4 = $dLon3*$dLon; 
		$dLon5 = $dLon4*$dLon;
		$dLon6 = $dLon5*$dLon;
	
		$N = $I + $II*$dLon2 + $III*$dLon4 + $IIIA*$dLon6;
		$E = $E0 + $IV*$dLon + $V*$dLon3 + $VI*$dLon5;
	
		return array('E' => abs($E), 'N' => abs($N));
	
	}
 
}

?>