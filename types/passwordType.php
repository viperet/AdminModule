<?

class passwordType extends textType {
	public $placeholder;

	public function fromRow($row) {
		$this->value = '';
	}

	public function toHtml() {
		return "<input type='password' name='{$this->name}' id='{$this->name}' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='' ".(!empty($this->placeholder)?"placeholder='".htmlspecialchars($this->placeholder)."' ":'')." />";
	}
	
	public function toSql() {
		if($this->value == '') return "";
		return "`{$this->name}`= md5('". mysql_real_escape_string($this->value)."')";
		
	}
	
}