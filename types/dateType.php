<?php

class dateType extends textType {

	public function toHtml() {
		if(empty($this->value) && $this->required)
			$this->value=time();
		if(empty($this->value))
			$date = "";
		else
			$date = date("d.m.Y", $this->value);
		if($this->readonly)
			return "
	    <div class='input-group' id='{$this->name}'>
	        <input type='text' class='form-control' name='{$this->name}' value='{$date}' readonly='1' />
	        <span class='input-group-addon'><span class='glyphicon glyphicon-calendar'></span></span>
		</div>";
		else		
			return "
	    <div class='input-group date' id='{$this->name}'>
	        <input type='text' class='form-control' name='{$this->name}' value='{$date}' placeholder='"._('DD.MM.YYYY')."' />
	        <span class='input-group-addon'><span class='glyphicon glyphicon-calendar'></span></span>
		</div>";
	}
	
	public function toSql() {
		if($this->readonly) return "";

		if(empty($this->value))
			$date = "NULL";
		else
			$date = "'".date('Y-m-d', (int)$this->value)."'";
		
		return "`{$this->name}`= {$date}";
	}

	public function toString() {
		if(empty($this->value))
			$date = "";
		else
			$date = date("d.m.Y", $this->value);
		return $date;		
	}
	
	public function fromForm($value) {
		$this->value = strtotime($value[$this->name]);
	}
	
	public function fromRow($row) {
		if($row[$this->name] == '0000-00-00')
			$this->value = '';
		else
			$this->value = strtotime($row[$this->name]);
	}
	
	public static function pageHeader() {
?>
<script type="text/javascript">
	$(function() {
		if($.fn.datetimepicker.defaults.locale !== undefined)
			$('.input-group.date').datetimepicker({locale: 'ru', format: 'DD.MM.YYYY'});
		else
			$('.input-group.date').datetimepicker({language: 'ru', format: 'DD.MM.YYYY'});
	});
</script>
<?php
	}
	
}