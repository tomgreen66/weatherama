var angle_from_north = 0.0;
var angle_from_horizon = 0.0;
var dir_archive = new Array();
var dir_archive2 = new Array();

var cnv_w;
var cnv_h;

var app = {
	
	currentLocation : {},
	
	iconFullSize : 267,
	
	objectStore:[],
	
	/*
	angle_from_north : 0.0,
	angle_from_horizon : 0.0,
	dir_archive : new Array(),
	dir_archive2 : new Array(),
	*/
	
	init : function() {
		this.log('init');
		this.getLocation();
		cnv_w = $('#canvas').width();
		cnv_h = $('#canvas').height();
		
	},
	
	log: function(msg){
		$('#log').html(msg);
	},
	getLocation : function() {
		var scope = this;
		this.log('get location');
		navigator.geolocation.getCurrentPosition(function(position) {
			
			//console.log(position);
			scope.log('got location');
			scope.currentLocation.latitude  = position.coords.latitude;
			scope.currentLocation.longitude = position.coords.longitude;
			
			scope.getLocationData();
			
			//return true;
			
		});
		return true;
		
	},
	
	getLocationData : function() {
		
		var scope = this;

		//if(this.currentLocation.latitude && this.currentLocation.longitude) {
			$.ajax({url : '/gateway.php',
					type : 'get',
					/*
					data : {'lat':51.8,//scope.currentLocation.latitude,
							'lng':-3},//scope.currentLocation.longitude},
							*/
					data : {'lat':scope.currentLocation.latitude,
							'lng':scope.currentLocation.longitude},
					dataType : 'json',
					success : function(data, textStatus, jqXHR) { 
									scope.loadElements(data, textStatus, jqXHR); 
			
									scope.log('addevent');
									window.addEventListener("deviceorientation", $.throttle(500, scope.orientationChange));
									//window.addEventListener("deviceorientation",  scope.orientationChange);

								}
					});
			return true;
		//}else{
		
		//	throw exception('No Location Data');
			
		//}
		
	},
	
	loadElements : function(data, textStatus, jqXHR) {
		var response = data.datapoints;
		//console.log(response);
		var str = '';
		var item;
		var screenPosition;
		for(var i = 0; i<response.length; i++) {
			
			item = response[i];
			
			screenPosition = this.translateOverheadCoordsToScreen(item['x'], item['y'], item['z'], item['distance']);
			this.positionSVG(item.type, screenPosition.x, screenPosition.y, item['z'], item.distance, screenPosition.scale, item.intensity);
			
			/*
			for(var k in response[i]) {
				str += k +' : '+response[i][k] + "\n";
			}
			*/
		}
		this.log('elements loaded');
		
		//$('#response').text(str);
	},
	
	orientationChange : function(event) {
		var scope = this;
		var smoothing_size = 5;
		//this.log('event fired');
		var angle_now = event.beta; 
		var rads = angle_now * (Math.PI/180);
	    var direction = 0;
	    if (event.webkitCompassHeading != undefined) {
	        direction = event.webkitCompassHeading;
	    }else if (event.alpha != null) {
	        direction = event.alpha;
	    }
	    
	    
	    dir_archive.push(direction);
		if (dir_archive.length>smoothing_size) {
		  dir_archive.shift();
		}
		direction=0.0
		for (i=0;i<dir_archive.length;i++) {
		  direction +=dir_archive[i];
		}  
		direction/=dir_archive.length;
		
		
		dir_archive2.push(event.gamma -90);
		if (dir_archive2.length>smoothing_size) {
		  dir_archive2.shift();
		}
		var counter=0.0
		for (i=0;i<dir_archive2.length;i++) {
		  counter +=dir_archive2[i];
		}  
		counter/=dir_archive2.length;	
		
	    angle_from_north = 180 - direction;
	    angle_from_north = angle_from_north * Math.PI / 180.0;
	    angle_from_horizon = counter;
	    angle_from_horizon = angle_from_horizon * Math.PI / 180.0;

	    
		
		// horizon?
		
		
		// each icon
		$('.icon').each(function(index) {
			
			var positionObj = $(this).attr('rel');
			var parts = positionObj.split(',');
			var r = {};
			for(var k in parts) {
				var v = parts[k].split(':');
				r[v[0]] = (v[1]*1);
			}
			var position = app.translateIconToScreenPosition(r.x, r.y, r.z);
			app.log(position.x);
			
			$(this).css('left',(position.x*cnv_w)+'px');
			$(this).css('top',(position.y*cnv_h)+'px');
			//$(this).css('width',(this.iconFullSize*position.scale)+'px');
		});
		
	},
	
	positionSVG : function(file, x, y, z, distance, scale, intensity) {
		// x, y, scale
		
		var newsize = this.iconFullSize*scale;
		var zindex = 50000 - distance;
		var el = $('#canvas').append('<img rel="x:'+x+',y:'+y+',z:'+z+',distance:'+distance+'" style="z-index:'+zindex+'; left:'+x+'px; top:'+y+'px" class="icon" src="/etc/icons/' + file + '.'+ intensity +'.png" width="'+newsize+'px" />');
	},
	
	/*
	getObjectKey : function(x, y, z, distance){
		return 'x:'+x+'y:'+y+'z:'+z+'distance:'+distance;
	},
	*/
	
	/**
	 * return {x, y, scale}
	 */
	translateOverheadCoordsToScreen : function(x, y, z, distance) {
		
		// x, y
		//var out = Math.atan(x/y);
		var out = (x / 4) + ($('#canvas').width() / 2);
		//console.log(out);
		
		
		
		
		
		// scale
		var dist = (distance/1000);
		if(dist > 20) {
			dist = 20;
		}
		var distFactor = 20 - dist;
		var imgRatio =  distFactor / 20; // 20 km horizon
		
		return {x: out, y: 30, scale:imgRatio}
	},
	
	/**
	 * return {x, y, scale}
	 */
	translateIconToScreenPosition : function(x, y, z) {
		
		// Z
	    var r_xyz = Math.sqrt(x * x + y * y + z * z);
	    var r_xy = Math.sqrt(x * x + y * y);
	    var theta_z = Math.atan2(z, r_xy);
	    var dtheta_camera_centre = theta_z - angle_from_horizon;
	   /*
	    if (dtheta_camera_centre > 0.15 || dtheta_camera_centre < -0.15) {
	        return [null, null]
	    }
	    */
	    var theta_z_down_from_top_of_camera = (0.15 - dtheta_camera_centre);
	    // XY
	    var theta_xy = Math.atan2(x, y);
	    var dtheta_xy_camera_centre = angle_from_north - theta_xy;
	   /*
	    if (dtheta_xy_camera_centre > 0.3 || dtheta_xy_camera_centre < -0.3) {
	        return [null, null]
	    }
	    */
	    var theta_xy_right_from_left_of_camera = (0.3 - dtheta_xy_camera_centre);    
	    var x_camera_fraction = theta_xy_right_from_left_of_camera / 0.6;
	    var z_camera_fraction = theta_z_down_from_top_of_camera / 0.3;
	    return {x:x_camera_fraction, y:z_camera_fraction};

		//return {x:null, y:null, scale:null}
	}
	
	
	
	
	
}
