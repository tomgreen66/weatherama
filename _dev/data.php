<html>
	<head>
		<title></title>
	</head>
	<body>

<?
//			 6a429b5e-1bc6-4215-9360-05f458fbd3c3
$apikey =	'6a429b5e-1bc6-4215-9360-05f458fbd3c3';
$url = 'http://datapoint.metoffice.gov.uk/public/data/val/wxobs/all/json/all?res=hourly&key='.$apikey;
echo $url;
$json = file_get_contents($url);
echo $json;
$data = json_decode($json);
var_dump($data);
//file_put_contents('store/obs-'.time().'.data', serialize($data));

?>


	</body>
</html>