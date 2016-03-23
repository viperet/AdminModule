<?

class hiddenType extends coreType {
	public $placeholder;
	
	public function toHtml() {
		return "<input type='hidden' name='{$this->name}' id='{$this->name}' class='form_input {$this->class} ".(!$this->valid?'error':'')."' value='".$this->escape($this->value)."' />";
	}
	
	public function toHtmlLabel() {
		return "";
	}	
	
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`=".$this->db->escape($this->value);
		
	}
	
}