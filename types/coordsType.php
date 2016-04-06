<?php

class coordsType extends coreType {
	public $lat; // широта
	public $lon; // долгота

	
	public function fromForm($value) {
		$this->lat = $value['lat'];
		$this->lon = $value['lon'];
	}
	
	public function fromRow($row) {
		if (Database::isError($row)) {
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
			die(Database::showError($row));
		}

		$this->lat = $row['lat'];
		$this->lon = $row['lon'];
	}	

	public static function pageHeader() {
	?>
<script src="http://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
	<?php
	}
	
	public function toString() {

		return $this->escape($this->lat).",".$this->escape($this->lon);
	}
		
	public function toHtml() {
		$lat = $this->lat;
		$lon = $this->lon;
		if(empty($lon) || empty($lat)) {
			$lon = 30.523397;
			$lat = 50.450097;
		}
	
		return 
"<input type='text' name='lat' id='lat' class='form_input {$this->class} ".(!$this->valid?'error':'')."' value='".$this->escape($this->lat)."' placeholder='"._('Latitude')."' style='width:150px;' />&nbsp;
<input type='text' name='lon' id='lon' class='form_input {$this->class} ".(!$this->valid?'error':'')."' value='".$this->escape($this->lon)."' placeholder='"._('Longitude')."' style='width:150px;' />&nbsp; "._('You can edit coordinates by dragging point on the map').", 
<div id='map' style='width: 100%; height: 400px'></div>
<script>
ymaps.ready(function () {
    var map = new ymaps.Map('map', {
            center: [".$this->escape($lat).",".$this->escape($lon)."], 
            zoom: 17,
            controls: ['geolocationControl', 'zoomControl', 'typeSelector', 'fullscreenControl']
        });
        
		var searchControl = new ymaps.control.SearchControl({
            options: {
                noPlacemark: true
            }
        });
        map.controls.add(searchControl);
		searchControl.events.add('resultselect', function (e) {
	        var index = e.get('index');
	        searchControl.getResult(index).then(function (res) {
				var coords = res.geometry.getCoordinates();
				point.geometry.setCoordinates( coords );
				$('#lat').val(coords[0]);
				$('#lon').val(coords[1]);
			});        
		});
		var point = new ymaps.Placemark([".$this->escape($lat).",".$this->escape($lon)."], { 
			hintContent: 'Объект', 
		    balloonContent: '', 
		},
		{
		    draggable: true,
		});
		point.events.add('dragend', function (e) {
			var thisPlacemark = e.get('target');
			// Определение координат метки
			var coords = thisPlacemark.geometry.getCoordinates();
			$('#lat').val(coords[0]);
			$('#lon').val(coords[1]);
   		});
		map.geoObjects.add(point); 
}); 
</script>
";
	}
	
	public function toSql() {
		if($this->readonly) return "";
		return "`lat`='". mysql_real_escape_string($this->lat)."',  `lon`='". mysql_real_escape_string($this->lon)."' ";
		
	}
	
}