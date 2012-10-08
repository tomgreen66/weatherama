<?php
/*
distances in km
type - one of ( rain, lightning, shower, drizzle )
*/
ini_set("display_errors","1");
ERROR_REPORTING(E_ALL);

if( isset($_GET['lng']) && isset($_GET['lat']) ){

	$lng = $_GET['lng'];
	$lat = $_GET['lat'];
	
	if( is_numeric($lng) && is_numeric($lat) ){
		
		$jsonReturn = array('datapoints' => array(

				array('x'=> 5,'y'=>5, 'z'=>1, 'type'=>'rain', 'intensity'=>1, 'heading'=>0 ),
				array('x'=> 6,'y'=>7, 'z'=>1, 'type'=>'rain', 'intensity'=>1, 'heading'=>0 ),
				array('x'=> 8,'y'=>9, 'z'=>1, 'type'=>'rain', 'intensity'=>1, 'heading'=>0 )
															  
			)

		);
	
	
	}else{
	
		$jsonReturn = array('error'=>'data not numeric');
		
	}


}else{
	$jsonReturn = array('error'=>'no request data sent');
}

echo json_encode($jsonReturn);
?>