<?php
/*
distances in km
type - one of ( rain, lightning, shower, drizzle )
*/
ini_set("display_errors","1");
ERROR_REPORTING(E_ALL);

include('inc/config.inc.php');



if( isset($_GET['lng']) && isset($_GET['lat']) ){

	$lng = $_GET['lng'];
	$lat = $_GET['lat'];

	
	if( is_numeric($lng) && is_numeric($lat) ){
	
		//do lat lng to x y conversion
		
		$os_grid = Geolocation::latLng2osGrid($lat,$lng);
		
		
			
		$q = "SELECT d.* 
		FROM datapoints d, datatypes t 
		WHERE 
		d.type_id = t.type_id
		AND
		t.type = 'rain'";
		
		$searchDistance = 20000;
			// o.easting - $os_grid['E'] as x , o.northing - $os_grid['N'] as y
		$q = 'SELECT o.z, o.intensity, (o.easting - '.round($os_grid['E']).') as x , (o.northing - '.round($os_grid['N']).') as y, t.type, ROUND( SQRT(POW(( o.easting - ' . round($os_grid['E']) . '),2) + POW((o.northing - ' . round($os_grid['N']) . '),2))) AS distance ';
		$q .= " FROM datapoints_os o, datatypes t"; 
		$q .= " WHERE ";
		$q .= " o.northing < " . round( $os_grid['N'] + $searchDistance ) ; 
		$q .= " AND o.northing > " . round( $os_grid['N'] - $searchDistance ) ;
		$q .= " AND o.easting < " . round( $os_grid['E'] + $searchDistance ) ;
		$q .= " AND o.easting > " . round( $os_grid['E'] - $searchDistance ) ;
		$q .= " AND o.type_id = t.type_id ";

		$q .= " HAVING distance <= " . $searchDistance ;
		$q .= " ORDER BY distance ASC" ;
		
		$datapoints = Common::getRows($q);
		
		//var_dump( )
		$jsonReturn = array('datapoints' => $datapoints);
	
	
	}else{
	
		$jsonReturn = array('error'=>'data not numeric');
		
	}


}else{
	$jsonReturn = array('error'=>'no request data sent');
}

echo json_encode($jsonReturn);
?>