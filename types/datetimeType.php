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
			$date = date("d.m.Y H:i:s", $this->value);
		return "<input type='text' name='{$this->name}' id='{$this->name}' class='form-control form_datetime {$this->class} ".(!$this->valid?'error':'')."' value='".$date."' placeholder='ДД.ММ.ГГГГ ЧЧ:ММ:СС' />";
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
	
}