<?

class dateType extends datetimeType {
	public $format = 'datetime'; // unixtime

	public function toHtml() {
		if(empty($this->value) && $this->required)
			$this->value=time();
		if(empty($this->value))
			$date = "";
		else
			$date = date("d.m.Y", $this->value);

		return "<input type='text' name='{$this->name}' id='{$this->name}' class='form_input form_date {$this->class} ".(!$this->valid?'error':'')."' value='".$date."' placeholder='ДД.ММ.ГГГГ' />";
	}
	

	public function toString() {
		if(empty($this->value))
			$date = "";
		else
			$date = date("d.m.Y", $this->value);
		return $date;		
	}
	
}