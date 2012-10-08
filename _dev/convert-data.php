<html>
	<head>
		<title></title>
	</head>
	<body>

<?
include('config.inc.php');

$q = "SELECT d.* FROM datapoints d, datatypes t 
		WHERE 
		d.type_id = t.type_id
		AND
		t.type = 'rain'

		";
		
$rows = Common::getRows($q);
foreach($rows as $row){
	$os_grid = Geolocation::latLng2osGrid($row['y'],$row['x']);
	$q = "INSERT INTO datapoints_os (easting,northing,z,type_id,intensity ) VALUES 
	(" . $os_grid['E'] ."," . $os_grid['N'] . "," . $row['z'] . "," . $row['type_id'] . "," . $row['intensity'] ." ) ";
	Common::sendQuery($q);
}
?>
	</body>
</html>lat 57   lng -4.45