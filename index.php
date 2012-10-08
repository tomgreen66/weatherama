<!DOCTYPE html>

<html>
<head>

<title>App</title>

<link rel="stylesheet" href="/css/styles.css" />
<style type="text/css">@import "/js/jquery/jquery.svg.package-1.4.5/jquery.svg.css";</style> 

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="/js/jquery/jquery.throttle.js"></script>
<script src="/js/app.js"></script>

<script type="text/javascript">
	
	$('document').ready(function() {
		
		try {
			app.init();
		}catch(e) {
			console.log(e);
			alert('caught an error');
		}
		
	});

</script>

</head>
<body>
  <p id="log">log</p>
  <div id="canvas" style="width:800px; height:600px; border:1px solid #CCC; display:block; overflow:hidden;"></div>
</body>