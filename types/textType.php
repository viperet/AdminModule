<?

class textType extends coreType {
	public $placeholder;
	public $readonly = false;
	public $values = array();

	public function toHtml() {
		return "<input type='text' name='{$this->name}' id='{$this->name}' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='".$this->escape($this->value)."' ".
			(!empty($this->placeholder)?"placeholder='".htmlspecialchars($this->placeholder)."' ":'').
			(!empty($this->readonly)?"readonly":'').
			" />";
	}
	
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`='". mysql_real_escape_string($this->value)."'";
		
	}
	public function getValues() {
		if(count($this->values)>0) {
			return $this->values;
		} else {
			return parent::getValues();
		}
	}	
}