<?

class checkboxType extends textType {
	public function toString() {
		return ($this->value?'Да':'Нет');
	}
	public function toHtml() {
		return "<input type='checkbox' name='{$this->name}' id='{$this->name}' class='form_checkbox {$this->class} ".(!$this->valid?'error':'')."' ".($this->value?'checked':'')." />";
	}
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`='". ($this->value=='on'?1:0)."'";
		
	}	
}