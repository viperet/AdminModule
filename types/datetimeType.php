<?

class datetimeType extends textType {
	public $format = 'datetime'; // хранится в timestamp или datetime 
				// unixtime - дата хранится в виде числа unixtimestamp

	public function toHtml() {
		if(empty($this->value) && $this->required)
			$this->value=time();
		if(empty($this->value))
			$date = "";
		else
			$date = date("d.m.Y H:i", $this->value);
		if($this->readonly)
			return "
	    <div class='input-group' id='{$this->name}'>
	        <input type='text' class='form-control' name='{$this->name}' value='{$date}' readonly='1' />
	        <span class='input-group-addon'><span class='glyphicon glyphicon-calendar'></span></span>
		</div>";
		else		
			return "
	    <div class='input-group datetime' id='{$this->name}'>
	        <input type='text' class='form-control' name='{$this->name}' value='{$date}' placeholder='"._('DD.MM.YYYY HH:MM')."' />
	        <span class='input-group-addon'><span class='glyphicon glyphicon-calendar'></span></span>
		</div>";
	}
	
	public function toSql() {
		if($this->readonly) return "";

		if(empty($this->value))
			$date = "NULL";
		elseif($this->format == 'datetime') 
			$date = "'".date('Y-m-d H:i:s', (int)$this->value)."'";
///			$date = "FROM_UNIXTIME(". (int)$this->value.")";
		elseif($this->format == 'unixtime') 
			$date = (int)$this->value;
		
		return "`{$this->name}`= {$date}";
	}

	public function toString() {
		if(empty($this->value))
			$date = "";
		else
			$date = date("d.m.Y H:i:s", $this->value);
		return $date;		
	}
	
	public function fromForm($value) {
		$this->value = strtotime($value[$this->name]);
	}
	
	public function fromRow($row) {
		if($this->format == 'datetime') {
			if($row[$this->name] == '0000-00-00' || $row[$this->name] == '0000-00-00 00:00:00')
				$this->value = '';
			else
				$this->value = strtotime($row[$this->name]);
		} elseif($this->format == 'unixtime')
			$this->value = $row[$this->name];
	}
	
	public static function pageHeader() {
?>
<script type="text/javascript">
	$(function() {
		if($.fn.datetimepicker.defaults.locale !== undefined)
			$('.input-group.datetime').datetimepicker({locale: 'ru'});
		else
			$('.input-group.datetime').datetimepicker({language: 'ru'});
	});
</script>
<?
	}
	
}